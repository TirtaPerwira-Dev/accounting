<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Journal;
use App\Models\JournalDetail;
use App\Models\Company;
use App\Models\NomorBantu;
use Carbon\Carbon;

class JournalSeeder extends Seeder
{
    public function run(): void
    {
        // Get first company
        $company = Company::first();
        if (!$company) {
            $this->command->info('No company found. Please seed companies first.');
            return;
        }

        // Get accounts untuk demo - menggunakan NomorBantu SAKEP
        $kasAccount = NomorBantu::where('nm_bantu', 'LIKE', '%Kas%')
            ->where('kel', '1') // Aktiva
            ->first();

        $piutangAccount = NomorBantu::where('nm_bantu', 'LIKE', '%Piutang%')
            ->where('kel', '1') // Aktiva
            ->first();

        $pendapatanAccount = NomorBantu::where('nm_bantu', 'LIKE', '%Pendapatan%')
            ->where('kel', '3') // Pendapatan
            ->first();

        $biayaAccount = NomorBantu::where('nm_bantu', 'LIKE', '%Biaya%')
            ->where('kel', '4') // Biaya
            ->first();

        if (!$kasAccount || !$piutangAccount || !$pendapatanAccount) {
            $this->command->info('Required SAKEP accounts not found. Using defaults from seeder.');

            // Fallback ke akun yang pasti ada dari SAKEP
            $kasAccount = NomorBantu::where('no_bantu', '10')->first(); // Kas Besar
            $piutangAccount = NomorBantu::where('no_bantu', '10')->where('nm_bantu', 'LIKE', '%Piutang%')->first();
            $pendapatanAccount = NomorBantu::where('nm_bantu', 'LIKE', '%Pendapatan%')->first();
            $biayaAccount = NomorBantu::where('nm_bantu', 'LIKE', '%Biaya%')->first();
        }

        // Sample Journal 1: PENERIMAAN - Penjualan Tunai
        try {
            $journal1 = Journal::create([
                'company_id' => $company->id,
                'transaction_date' => Carbon::today()->subDays(5),
                'description' => 'Penerimaan kas dari penjualan air tunai - RT 001 s/d RT 010',
                'transaction_type' => Journal::TYPE_PENERIMAAN,
                'total_amount' => 5500000,
                'status' => 'draft',
                'created_by' => 1,
            ]);

            // Generate reference
            $journal1->reference = 'KM-' . $journal1->transaction_date->format('Ym') . '-' . str_pad($journal1->id, 3, '0', STR_PAD_LEFT);
            $journal1->save();

            // Journal details
            if ($kasAccount && $pendapatanAccount) {
                JournalDetail::create([
                    'journal_id' => $journal1->id,
                    'nomor_bantu_id' => $kasAccount->id,
                    'debit' => 5500000,
                    'credit' => 0,
                    'description' => 'Penerimaan kas dari penjualan air',
                ]);

                JournalDetail::create([
                    'journal_id' => $journal1->id,
                    'nomor_bantu_id' => $pendapatanAccount->id,
                    'debit' => 0,
                    'credit' => 5500000,
                    'description' => 'Pendapatan penjualan air bersih',
                ]);

                $journal1->update(['status' => 'posted']);
                $this->command->info('Journal PENERIMAAN 1 created and posted: ' . $journal1->reference);
            }
        } catch (\Exception $e) {
            $this->command->error('Error creating journal 1: ' . $e->getMessage());
        }

        // Sample Journal 2: PENERIMAAN - Penjualan Kredit
        try {
            $journal2 = Journal::create([
                'company_id' => $company->id,
                'transaction_date' => Carbon::today()->subDays(3),
                'description' => 'Penerimaan tagihan air dari pelanggan - Periode November 2025',
                'transaction_type' => Journal::TYPE_PENERIMAAN,
                'total_amount' => 8750000,
                'status' => 'draft',
                'created_by' => 1,
            ]);

            // Generate reference
            $journal2->reference = 'KM-' . $journal2->transaction_date->format('Ym') . '-' . str_pad($journal2->id, 3, '0', STR_PAD_LEFT);
            $journal2->save();

            // Journal details
            if ($kasAccount && $piutangAccount) {
                JournalDetail::create([
                    'journal_id' => $journal2->id,
                    'nomor_bantu_id' => $kasAccount->id,
                    'debit' => 8750000,
                    'credit' => 0,
                    'description' => 'Penerimaan kas dari pelanggan',
                ]);

                JournalDetail::create([
                    'journal_id' => $journal2->id,
                    'nomor_bantu_id' => $piutangAccount->id,
                    'debit' => 0,
                    'credit' => 8750000,
                    'description' => 'Pelunasan piutang tagihan air',
                ]);

                $journal2->update(['status' => 'posted']);
                $this->command->info('Journal PENERIMAAN 2 created and posted: ' . $journal2->reference);
            }
        } catch (\Exception $e) {
            $this->command->error('Error creating journal 2: ' . $e->getMessage());
        }

        // Sample Journal 3: PENGELUARAN - Pembelian Bahan Kimia
        try {
            $journal3 = Journal::create([
                'company_id' => $company->id,
                'transaction_date' => Carbon::today()->subDays(2),
                'description' => 'Pembayaran pembelian bahan kimia untuk pengolahan air',
                'transaction_type' => Journal::TYPE_PENGELUARAN,
                'total_amount' => 3200000,
                'status' => 'draft',
                'created_by' => 1,
            ]);

            // Generate reference
            $journal3->reference = 'KK-' . $journal3->transaction_date->format('Ym') . '-' . str_pad($journal3->id, 3, '0', STR_PAD_LEFT);
            $journal3->save();

            // Journal details
            if ($biayaAccount && $kasAccount) {
                JournalDetail::create([
                    'journal_id' => $journal3->id,
                    'nomor_bantu_id' => $biayaAccount->id,
                    'debit' => 3200000,
                    'credit' => 0,
                    'description' => 'Pembelian bahan kimia kaporit',
                ]);

                JournalDetail::create([
                    'journal_id' => $journal3->id,
                    'nomor_bantu_id' => $kasAccount->id,
                    'debit' => 0,
                    'credit' => 3200000,
                    'description' => 'Pembayaran tunai bahan kimia',
                ]);

                $journal3->update(['status' => 'posted']);
                $this->command->info('Journal PENGELUARAN 3 created and posted: ' . $journal3->reference);
            }
        } catch (\Exception $e) {
            $this->command->error('Error creating journal 3: ' . $e->getMessage());
        }

        // Sample Journal 4: PENGELUARAN Draft - Pembayaran Listrik PLN
        try {
            $journal4 = Journal::create([
                'company_id' => $company->id,
                'transaction_date' => Carbon::today(),
                'description' => 'Pembayaran tagihan listrik PLN bulan November 2025 - DRAFT',
                'transaction_type' => Journal::TYPE_PENGELUARAN,
                'total_amount' => 1850000,
                'status' => 'draft',
                'created_by' => 1,
            ]);

            // Generate reference
            $journal4->reference = 'KK-' . $journal4->transaction_date->format('Ym') . '-' . str_pad($journal4->id, 3, '0', STR_PAD_LEFT);
            $journal4->save();

            // Journal details
            if ($biayaAccount && $kasAccount) {
                JournalDetail::create([
                    'journal_id' => $journal4->id,
                    'nomor_bantu_id' => $biayaAccount->id,
                    'debit' => 1850000,
                    'credit' => 0,
                    'description' => 'Beban listrik PLN November',
                ]);

                JournalDetail::create([
                    'journal_id' => $journal4->id,
                    'nomor_bantu_id' => $kasAccount->id,
                    'debit' => 0,
                    'credit' => 1850000,
                    'description' => 'Pembayaran tagihan listrik PLN',
                ]);

                // Keep as draft - don't post
                $this->command->info('Journal PENGELUARAN 4 created (draft): ' . $journal4->reference);
            }
        } catch (\Exception $e) {
            $this->command->error('Error creating journal 4: ' . $e->getMessage());
        }

        $this->command->info('SAKEP Journal seeder completed successfully!');
        $this->command->info('✅ Penerimaan: ' . Journal::penerimaan()->count() . ' journals');
        $this->command->info('✅ Pengeluaran: ' . Journal::pengeluaran()->count() . ' journals');
        $this->command->info('✅ Total: ' . Journal::count() . ' journals');
    }
}
