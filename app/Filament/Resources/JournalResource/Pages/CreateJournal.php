<?php

namespace App\Filament\Resources\JournalResource\Pages;

use App\Filament\Resources\JournalResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;

class CreateJournal extends CreateRecord
{
    protected static string $resource = JournalResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure created_by is set
        $data['created_by'] = Auth::id();

        // Set company_id from user's default company or first company
        if (empty($data['company_id'])) {
            $user = Auth::user();
            $data['company_id'] = $user->company_id ?? \App\Models\Company::first()?->id ?? 1;
        }

        // Set default status if not provided
        if (empty($data['status'])) {
            $data['status'] = 'draft';
        }

        // Don't set reference manually, let the model handle it
        unset($data['reference']);

        // Convert simple_entries to details format if present
        if (isset($data['simple_entries']) && is_array($data['simple_entries'])) {
            $details = [];
            foreach ($data['simple_entries'] as $entry) {
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
            $data['details'] = $details;
        }

        // Remove form-only fields
        unset($data['simple_entries'], $data['journal_template']);

        // Validate journal is balanced
        if (isset($data['details']) && is_array($data['details'])) {
            $totalDebit = collect($data['details'])->sum(function ($item) {
                return is_numeric($item['debit'] ?? 0) ? floatval($item['debit']) : 0;
            });

            $totalCredit = collect($data['details'])->sum(function ($item) {
                return is_numeric($item['credit'] ?? 0) ? floatval($item['credit']) : 0;
            });

            $diff = abs($totalDebit - $totalCredit);

            if ($diff > 0.01) {
                throw new \Exception(
                    "Jurnal tidak seimbang! Total Debit: Rp " . number_format($totalDebit, 2, ',', '.') .
                        " | Total Kredit: Rp " . number_format($totalCredit, 2, ',', '.') .
                        " | Selisih: Rp " . number_format($diff, 2, ',', '.') .
                        ". Pastikan Total Debit = Total Kredit."
                );
            }

            $data['total_amount'] = $totalDebit;
        }

        // Log the data being saved for debugging
        Log::info('Journal form data before create:', [
            'main_data' => Arr::except($data, ['details']),
            'details_count' => count($data['details'] ?? []),
            'details' => $data['details'] ?? [],
        ]);

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        // Extract details from data
        $details = $data['details'] ?? [];
        unset($data['details']);

        // Create the journal record
        $journal = static::getModel()::create($data);

        // Create journal details with SAKEP structure
        if (!empty($details)) {
            foreach ($details as $index => $detail) {
                // Get SAKEP hierarchy from nomor_bantu
                $nomorBantu = \App\Models\NomorBantu::find($detail['nomor_bantu_id']);

                if ($nomorBantu) {
                    $journal->details()->create([
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
        }

        return $journal;
    }

    protected function afterCreate(): void
    {
        // After journal is created, update total_amount
        $journal = $this->record;
        $journal->refresh();

        $totalDebit = $journal->details()->sum('debit');
        $journal->update(['total_amount' => $totalDebit]);

        Log::info('Journal created successfully:', [
            'journal_id' => $journal->id,
            'reference' => $journal->reference,
            'details_count' => $journal->details->count(),
            'total_debit' => $journal->totalDebit,
            'total_credit' => $journal->totalCredit,
            'can_be_posted' => $journal->canBePosted(),
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
