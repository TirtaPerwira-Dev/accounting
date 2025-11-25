<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use App\Models\Journal;
use App\Models\JournalDetail;
use App\Models\NomorBantu;
use Carbon\Carbon;

class AddSampleTransactions extends Command
{
    protected $signature = 'sample:transactions';
    protected $description = 'Add sample transactions for testing financial reports';

    public function handle()
    {
        $company = Company::first();
        if (!$company) {
            $this->error('No company found. Please create a company first.');
            return 1;
        }

        $this->info('Adding sample transactions...');

        // Sample transactions for PDAM
        $transactions = [
            [
                'description' => 'Penjualan Air Bulan November 2024',
                'reference' => 'INV/2024/11/001',
                'date' => '2024-11-01',
                'entries' => [
                    ['account_name' => 'Piutang Rekening Air', 'debit' => 25000000, 'credit' => 0],
                    ['account_name' => 'Pendapatan Harga Air', 'debit' => 0, 'credit' => 22727273],
                    ['account_name' => 'Utang Pajak PPN', 'debit' => 0, 'credit' => 2272727],
                ]
            ],
            [
                'description' => 'Pembayaran Gaji Karyawan November 2024',
                'reference' => 'SAL/2024/11/001',
                'date' => '2024-11-05',
                'entries' => [
                    ['account_name' => 'Gaji dan Honor Pengawai', 'debit' => 15000000, 'credit' => 0],
                    ['account_name' => 'Utang Pajak Pasal 21', 'debit' => 0, 'credit' => 750000],
                    ['account_name' => 'Bank BPD Capem Pasar Kota', 'debit' => 0, 'credit' => 14250000],
                ]
            ],
            [
                'description' => 'Pembelian Bahan Kimia (Kaporit)',
                'reference' => 'PUR/2024/11/001',
                'date' => '2024-11-03',
                'entries' => [
                    ['account_name' => 'Persediaan Bahan Operasi Kimia', 'debit' => 5500000, 'credit' => 0],
                    ['account_name' => 'Pajak Dimuka (PPN)', 'debit' => 550000, 'credit' => 0],
                    ['account_name' => 'Hutang Usaha', 'debit' => 0, 'credit' => 6050000],
                ]
            ],
            [
                'description' => 'Penerimaan Kas dari Pelanggan',
                'reference' => 'RC/2024/11/001',
                'date' => '2024-11-07',
                'entries' => [
                    ['account_name' => 'Kas Besar', 'debit' => 18000000, 'credit' => 0],
                    ['account_name' => 'Piutang Rekening Air', 'debit' => 0, 'credit' => 18000000],
                ]
            ],
            [
                'description' => 'Biaya Listrik PLN Bulan Oktober',
                'reference' => 'EXP/2024/11/001',
                'date' => '2024-11-02',
                'entries' => [
                    ['account_name' => 'Biaya Listrik PLN', 'debit' => 3200000, 'credit' => 0],
                    ['account_name' => 'Bank BPD Capem Pasar Kota', 'debit' => 0, 'credit' => 3200000],
                ]
            ],
        ];

        $journalId = 0;
        foreach ($transactions as $transaction) {
            $journalId++;

            // Create journal
            $journal = Journal::create([
                'company_id' => $company->id,
                'journal_number' => 'JV' . str_pad($journalId, 4, '0', STR_PAD_LEFT) . '/2024',
                'transaction_date' => Carbon::parse($transaction['date']),
                'description' => $transaction['description'],
                'reference' => $transaction['reference'],
                'status' => 'posted',
                'created_by' => 1,
            ]);

            $this->info("Created journal: {$journal->journal_number}");

            // Create journal details
            foreach ($transaction['entries'] as $entry) {
                // Find account by name
                $nomorBantu = NomorBantu::where('nm_bantu', 'like', '%' . $entry['account_name'] . '%')->first();

                if (!$nomorBantu) {
                    $this->warn("Account not found: {$entry['account_name']}");
                    continue;
                }

                JournalDetail::create([
                    'journal_id' => $journal->id,
                    'kelompok_id' => $nomorBantu->rekening->kelompok_id,
                    'rekening_id' => $nomorBantu->rekening_id,
                    'nomor_bantu_id' => $nomorBantu->id,
                    'description' => $transaction['description'],
                    'debit' => $entry['debit'],
                    'credit' => $entry['credit'],
                ]);

                $this->line("  - {$entry['account_name']}: Debit " . number_format($entry['debit']) . ", Credit " . number_format($entry['credit']));
            }
        }

        $this->info('Sample transactions added successfully!');
        $this->info('Total journals created: ' . count($transactions));

        return 0;
    }
}
