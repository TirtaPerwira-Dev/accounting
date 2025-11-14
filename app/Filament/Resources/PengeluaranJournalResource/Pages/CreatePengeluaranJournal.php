<?php

namespace App\Filament\Resources\PengeluaranJournalResource\Pages;

use App\Filament\Resources\PengeluaranJournalResource;
use App\Models\Journal;
use App\Models\JournalDetail;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class CreatePengeluaranJournal extends CreateRecord
{
    protected static string $resource = PengeluaranJournalResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            // Create journal record
            $journal = Journal::create([
                'company_id' => $data['company_id'] ?? 1,
                'transaction_date' => $data['transaction_date'],
                'description' => $data['description'],
                'reference' => $data['reference'],
                'total_amount' => $data['total_amount'],
                'status' => $data['status'],
                'transaction_type' => $data['transaction_type'],
                'created_by' => $data['created_by'],
            ]);

            // Create journal details from simple_entries
            foreach ($data['simple_entries'] ?? [] as $entry) {
                JournalDetail::create([
                    'journal_id' => $journal->id,
                    'nomor_bantu_id' => $entry['nomor_bantu_id'],
                    'debit' => $entry['account_type'] === 'debit' ? $entry['amount'] : 0,
                    'credit' => $entry['account_type'] === 'credit' ? $entry['amount'] : 0,
                    'description' => $entry['description'],
                ]);
            }

            return $journal->refresh();
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Jurnal pengeluaran kas berhasil dibuat!';
    }
}
