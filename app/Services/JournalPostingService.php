<?php

namespace App\Services;

use App\Models\Journal;
use App\Models\JournalDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class JournalPostingService
{
    /**
     * Post a single record to the General Ledger.
     */
    public function post(Model $record): ?Journal
    {
        if (!$record->is_confirmed) {
            throw new \Exception("Hanya jurnal yang sudah dikonfirmasi yang dapat diposting.");
        }

        if ($record->is_posted) {
            throw new \Exception("Jurnal ini sudah diposting sebelumnya.");
        }

        return DB::transaction(function () use ($record) {
            $entries = $record->generateJournalEntries();

            if (empty($entries)) {
                throw new \Exception("Tidak ada entri jurnal yang dihasilkan dari record ini.");
            }

            // 1. Create Journal Header
            $totalDebit = collect($entries)->sum('debit');
            $totalCredit = collect($entries)->sum('kredit');

            if (abs($totalDebit - $totalCredit) > 0.01) {
                throw new \Exception("Jurnal tidak seimbang! Total Debit: {$totalDebit}, Total Kredit: {$totalCredit}");
            }

            $journal = Journal::create([
                'company_id' => $record->company_id ?? 1,
                'transaction_date' => $record->tanggal->toDateString(),
                'reference' => $record->no_reff ?? ($record->bukti ?? $record->nomor_bukti),
                'description' => $record->keterangan ?? "Posting from " . class_basename($record),
                'transaction_type' => $this->inferTransactionType($record),
                'total_amount' => $totalDebit,
                'status' => 'posted',
                'created_by' => $record->created_by ?? Auth::id(),
                'posted_by' => Auth::id(),
                'posted_at' => now(),
            ]);

            // 2. Create Journal Details
            foreach ($entries as $index => $entry) {
                JournalDetail::create([
                    'journal_id' => $journal->id,
                    'kelompok_id' => $entry['kelompok_id'] ?? null,
                    'rekening_id' => $entry['rekening_id'] ?? null,
                    'nomor_bantu_id' => $entry['nomor_bantu_id'] ?? null,
                    'debit' => $entry['debit'],
                    'credit' => $entry['kredit'],
                    'description' => $entry['keterangan'] ?? $journal->description,
                    'line_number' => $index + 1,
                ]);
            }

            // 3. Update Source Record
            $record->update([
                'is_posted' => true,
                'posted_at' => now(),
                'posted_by' => Auth::id(),
                'journal_id' => $journal->id,
            ]);

            return $journal;
        });
    }

    /**
     * Bulk post multiple records.
     */
    public function postBulk(Collection $records): int
    {
        $count = 0;
        foreach ($records as $record) {
            try {
                $this->post($record);
                $count++;
            } catch (\Exception $e) {
                // You might want to log errors here or rethrow
                report($e);
            }
        }
        return $count;
    }

    /**
     * Infer transaction type for General Ledger based on model and entries.
     */
    protected function inferTransactionType(Model $record): string
    {
        $className = class_basename($record);
        
        if ($className === 'JurnalPenerimaanKas') {
            return Journal::TYPE_PENERIMAAN;
        }
        
        if ($className === 'JurnalBayarKasBank') {
            return Journal::TYPE_PENGELUARAN;
        }

        // Default to penerimaan if we can't be sure, or add more logic
        return Journal::TYPE_PENERIMAAN;
    }
}
