<?php

namespace App\Services;

use App\Models\Journal;
use App\Models\JournalDetail;
use App\Models\NomorBantu;
use App\Models\Rekening;
use App\Models\Kelompok;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class JournalService
{
    /**
     * Create a journal entry with validation
     */
    public function createJournal(array $data): Journal
    {
        return DB::transaction(function () use ($data) {
            // Validate journal data
            $this->validateJournalData($data);

            // Create journal header
            $journal = Journal::create([
                'company_id' => $data['company_id'],
                'transaction_date' => $data['transaction_date'],
                'description' => $data['description'],
                'reference' => $data['reference'] ?? null,
                'created_by' => Auth::id(),
            ]);

            // Create journal details
            if (isset($data['details']) && is_array($data['details'])) {
                foreach ($data['details'] as $index => $detail) {
                    $this->createJournalDetail($journal, $detail, $index + 1);
                }
            }

            // Validate balanced entries
            if (!$journal->refresh()->isBalanced) {
                throw new InvalidArgumentException('Journal entries must be balanced (total debit = total credit)');
            }

            // Update total amount
            $journal->update(['total_amount' => $journal->totalDebit]);

            return $journal;
        });
    }

    /**
     * Update existing journal
     */
    public function updateJournal(Journal $journal, array $data): Journal
    {
        if ($journal->status === 'posted') {
            throw new InvalidArgumentException('Cannot modify posted journal');
        }

        return DB::transaction(function () use ($journal, $data) {
            // Update header
            $journal->update([
                'transaction_date' => $data['transaction_date'],
                'description' => $data['description'],
                'reference' => $data['reference'] ?? $journal->reference,
            ]);

            // Delete existing details
            $journal->details()->delete();

            // Create new details
            if (isset($data['details']) && is_array($data['details'])) {
                foreach ($data['details'] as $index => $detail) {
                    $this->createJournalDetail($journal, $detail, $index + 1);
                }
            }

            // Validate balanced entries
            if (!$journal->refresh()->isBalanced) {
                throw new InvalidArgumentException('Journal entries must be balanced');
            }

            // Update total amount
            $journal->update(['total_amount' => $journal->totalDebit]);

            return $journal;
        });
    }

    /**
     * Post journal to general ledger
     */
    public function postJournal(Journal $journal): bool
    {
        if (!$journal->canBePosted()) {
            throw new InvalidArgumentException('Journal cannot be posted');
        }

        return $journal->post();
    }

    /**
     * Reverse posted journal
     */
    public function reverseJournal(Journal $journal): bool
    {
        if ($journal->status !== 'posted') {
            throw new InvalidArgumentException('Only posted journals can be reversed');
        }

        return $journal->reverse();
    }

    /**
     * Create automatic journal for common transactions (updated for SAKEP)
     */
    public function createSalesJournal(array $data): Journal
    {
        // Example: Water sales journal with SAKEP
        // Dr. Piutang Usaha (1301)
        // Cr. Pendapatan Air (8101)
        // Cr. PPN Keluaran (5006) if applicable

        $amount = $data['amount'];
        $ppnRate = $data['ppn_rate'] ?? 0;
        $ppnAmount = $amount * $ppnRate / 100;
        $baseAmount = $amount - $ppnAmount;

        $journalData = [
            'company_id' => $data['company_id'],
            'transaction_date' => $data['transaction_date'],
            'description' => $data['description'] ?? 'Penjualan Air',
            'reference' => $data['reference'] ?? null,
            'details' => [
                [
                    'nomor_bantu_id' => $data['receivable_nomor_bantu_id'] ?? null,
                    'rekening_id' => $data['receivable_rekening_id'] ?? null,
                    'kelompok_id' => $data['receivable_kelompok_id'] ?? null,
                    'debit' => $amount,
                    'credit' => 0,
                    'description' => 'Piutang penjualan air',
                ],
                [
                    'nomor_bantu_id' => $data['revenue_nomor_bantu_id'] ?? null,
                    'rekening_id' => $data['revenue_rekening_id'] ?? null,
                    'kelompok_id' => $data['revenue_kelompok_id'] ?? null,
                    'debit' => 0,
                    'credit' => $baseAmount,
                    'description' => 'Pendapatan air bersih',
                ],
            ],
        ];

        // Add PPN if applicable
        if ($ppnAmount > 0) {
            $journalData['details'][] = [
                'nomor_bantu_id' => $data['ppn_nomor_bantu_id'] ?? null,
                'rekening_id' => $data['ppn_rekening_id'] ?? null,
                'kelompok_id' => $data['ppn_kelompok_id'] ?? null,
                'debit' => 0,
                'credit' => $ppnAmount,
                'description' => 'PPN Keluaran 11%',
            ];
        }

        return $this->createJournal($journalData);
    }

    /**
     * Create payment journal (updated for SAKEP)
     */
    public function createPaymentJournal(array $data): Journal
    {
        // Dr. Expense Account
        // Cr. Cash/Bank Account

        $journalData = [
            'company_id' => $data['company_id'],
            'transaction_date' => $data['transaction_date'],
            'description' => $data['description'] ?? 'Pembayaran',
            'reference' => $data['reference'] ?? null,
            'details' => [
                [
                    'nomor_bantu_id' => $data['expense_nomor_bantu_id'] ?? null,
                    'rekening_id' => $data['expense_rekening_id'] ?? null,
                    'kelompok_id' => $data['expense_kelompok_id'] ?? null,
                    'debit' => $data['amount'],
                    'credit' => 0,
                    'description' => $data['expense_description'] ?? 'Beban',
                ],
                [
                    'nomor_bantu_id' => $data['cash_nomor_bantu_id'] ?? null,
                    'rekening_id' => $data['cash_rekening_id'] ?? null,
                    'kelompok_id' => $data['cash_kelompok_id'] ?? null,
                    'debit' => 0,
                    'credit' => $data['amount'],
                    'description' => $data['payment_description'] ?? 'Pembayaran tunai',
                ],
            ],
        ];

        return $this->createJournal($journalData);
    }

    /**
     * Validate journal data
     */
    private function validateJournalData(array $data): void
    {
        if (empty($data['company_id'])) {
            throw new InvalidArgumentException('Company ID is required');
        }

        if (empty($data['transaction_date'])) {
            throw new InvalidArgumentException('Transaction date is required');
        }

        if (empty($data['description'])) {
            throw new InvalidArgumentException('Description is required');
        }
    }

    /**
     * Create journal detail with validation for SAKEP
     */
    private function createJournalDetail(Journal $journal, array $data, int $lineNumber): JournalDetail
    {
        // Validate SAKEP account exists
        if (!empty($data['nomor_bantu_id'])) {
            $nomorBantu = NomorBantu::find($data['nomor_bantu_id']);
            if (!$nomorBantu) {
                throw new InvalidArgumentException('Invalid Nomor Bantu ID: ' . $data['nomor_bantu_id']);
            }
            $sakepData = [
                'nomor_bantu_id' => $data['nomor_bantu_id'],
                'rekening_id' => $nomorBantu->rekening_id,
                'kelompok_id' => $nomorBantu->rekening->kelompok_id,
            ];
        } elseif (!empty($data['rekening_id'])) {
            $rekening = Rekening::find($data['rekening_id']);
            if (!$rekening) {
                throw new InvalidArgumentException('Invalid Rekening ID: ' . $data['rekening_id']);
            }
            $sakepData = [
                'rekening_id' => $data['rekening_id'],
                'kelompok_id' => $rekening->kelompok_id,
            ];
        } elseif (!empty($data['kelompok_id'])) {
            $kelompok = Kelompok::find($data['kelompok_id']);
            if (!$kelompok) {
                throw new InvalidArgumentException('Invalid Kelompok ID: ' . $data['kelompok_id']);
            }
            $sakepData = ['kelompok_id' => $data['kelompok_id']];
        } else {
            throw new InvalidArgumentException('Must provide at least kelompok_id, rekening_id, or nomor_bantu_id');
        }

        // Validate amounts
        $debit = floatval($data['debit'] ?? 0);
        $credit = floatval($data['credit'] ?? 0);

        if ($debit == 0 && $credit == 0) {
            throw new InvalidArgumentException('Either debit or credit amount must be greater than zero');
        }

        if ($debit > 0 && $credit > 0) {
            throw new InvalidArgumentException('Cannot have both debit and credit amounts');
        }

        return $journal->details()->create(array_merge($sakepData, [
            'debit' => $debit,
            'credit' => $credit,
            'description' => $data['description'] ?? null,
            'line_number' => $lineNumber,
        ]));
    }

    /**
     * Get journal summary for dashboard
     */
    public function getJournalSummary(int $companyId, ?string $period = null): array
    {
        $query = Journal::where('company_id', $companyId)->where('status', 'posted');

        if ($period) {
            $query->whereMonth('transaction_date', date('m', strtotime($period)))
                ->whereYear('transaction_date', date('Y', strtotime($period)));
        }

        $journals = $query->get();

        return [
            'total_journals' => $journals->count(),
            'total_amount' => $journals->sum('total_amount'),
            'draft_count' => Journal::where('company_id', $companyId)->where('status', 'draft')->count(),
            'posted_count' => $journals->count(),
            'reversed_count' => Journal::where('company_id', $companyId)->where('status', 'reversed')->count(),
        ];
    }
}
