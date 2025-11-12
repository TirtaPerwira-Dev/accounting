<?php

namespace App\Filament\Resources\JournalResource\Pages;

use App\Filament\Resources\JournalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditJournal extends EditRecord
{
    protected static string $resource = JournalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn(): bool => $this->record->status === 'draft'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Convert journal details to simple_entries format for editing
        $simple_entries = $this->record->details()->orderBy('line_number')->get()->map(function ($detail) {
            return [
                'nomor_bantu_id' => $detail->nomor_bantu_id,
                'account_type' => $detail->debit > 0 ? 'debit' : 'credit',
                'amount' => $detail->debit > 0 ? $detail->debit : $detail->credit,
                'description' => $detail->description,
            ];
        })->toArray();

        $data['simple_entries'] = $simple_entries;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Extract details from simple_entries
        $simple_entries = $data['simple_entries'] ?? [];
        unset($data['simple_entries']);

        // Update main journal record
        $record->update($data);

        // Delete existing details
        $record->details()->delete();

        // Convert simple_entries to details and recreate
        if (!empty($simple_entries)) {
            $details = [];
            foreach ($simple_entries as $entry) {
                if (isset($entry['nomor_bantu_id']) && isset($entry['amount'])) {
                    // Clean and parse amount
                    $amount = $entry['amount'];
                    if (is_string($amount)) {
                        $amount = str_replace(['.', ',', ' ', 'Rp'], '', $amount);
                    }
                    $amount = is_numeric($amount) ? (float)$amount : 0;

                    if ($amount > 0) {
                        $details[] = [
                            'nomor_bantu_id' => $entry['nomor_bantu_id'],
                            'debit' => $entry['account_type'] === 'debit' ? $amount : 0,
                            'credit' => $entry['account_type'] === 'credit' ? $amount : 0,
                            'description' => $entry['description'] ?? '',
                        ];
                    }
                }
            }

            foreach ($details as $index => $detail) {
                // Get SAKEP hierarchy from nomor_bantu
                $nomorBantu = \App\Models\NomorBantu::find($detail['nomor_bantu_id']);

                if ($nomorBantu) {
                    $record->details()->create([
                        'nomor_bantu_id' => $detail['nomor_bantu_id'],
                        'rekening_id' => $nomorBantu->rekening_id,
                        'kelompok_id' => $nomorBantu->rekening->kelompok_id,
                        'debit' => $detail['debit'],
                        'credit' => $detail['credit'],
                        'description' => $detail['description'],
                        'line_number' => $index + 1,
                    ]);
                }
            }

            // Update total_amount
            $totalDebit = $record->details()->sum('debit');
            $record->update(['total_amount' => $totalDebit]);
        }

        return $record;
    }
}
