<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Services\JournalService;
use App\Models\Company;
use App\Models\ChartOfAccount;
use Carbon\Carbon;

class JournalSeeder extends Seeder
{
    public function run(): void
    {
        $journalService = new JournalService();

        // Get first company
        $company = Company::first();
        if (!$company) {
            $this->command->info('No company found. Please seed companies first.');
            return;
        }

        // Get accounts for demo
        $kasAccount = ChartOfAccount::where('company_id', $company->id)
            ->where('name', 'LIKE', '%Kas%')
            ->first();

        $piutangAccount = ChartOfAccount::where('company_id', $company->id)
            ->where('name', 'LIKE', '%Piutang%')
            ->first();

        $pendapatanAccount = ChartOfAccount::where('company_id', $company->id)
            ->where('name', 'LIKE', '%Pendapatan%')
            ->first();

        if (!$kasAccount || !$piutangAccount || !$pendapatanAccount) {
            $this->command->info('Required accounts not found. Please create COA first.');
            return;
        }

        // Sample Journal 1: Penjualan Tunai
        try {
            $journal1 = $journalService->createJournal([
                'company_id' => $company->id,
                'transaction_date' => Carbon::today()->subDays(5)->toDateString(),
                'description' => 'Penjualan air tunai - RT 001 s/d RT 010',
                'details' => [
                    [
                        'account_id' => $kasAccount->id,
                        'debit' => 5500000,
                        'credit' => 0,
                        'description' => 'Penerimaan kas dari penjualan air',
                    ],
                    [
                        'account_id' => $pendapatanAccount->id,
                        'debit' => 0,
                        'credit' => 5500000,
                        'description' => 'Pendapatan penjualan air bersih',
                    ],
                ],
            ]);
            $journal1->post();
            $this->command->info('Journal 1 created and posted: ' . $journal1->reference);
        } catch (\Exception $e) {
            $this->command->error('Error creating journal 1: ' . $e->getMessage());
        }

        // Sample Journal 2: Penjualan Kredit
        try {
            $journal2 = $journalService->createJournal([
                'company_id' => $company->id,
                'transaction_date' => Carbon::today()->subDays(3)->toDateString(),
                'description' => 'Penjualan air kredit - Periode November 2025',
                'details' => [
                    [
                        'account_id' => $piutangAccount->id,
                        'debit' => 12500000,
                        'credit' => 0,
                        'description' => 'Piutang tagihan air November',
                    ],
                    [
                        'account_id' => $pendapatanAccount->id,
                        'debit' => 0,
                        'credit' => 12500000,
                        'description' => 'Pendapatan air November',
                    ],
                ],
            ]);
            $journal2->post();
            $this->command->info('Journal 2 created and posted: ' . $journal2->reference);
        } catch (\Exception $e) {
            $this->command->error('Error creating journal 2: ' . $e->getMessage());
        }

        // Sample Journal 3: Draft (belum di-post)
        try {
            $journal3 = $journalService->createJournal([
                'company_id' => $company->id,
                'transaction_date' => Carbon::today()->toDateString(),
                'description' => 'Pembelian bahan kimia - Draft',
                'details' => [
                    [
                        'account_id' => ChartOfAccount::where('company_id', $company->id)->where('type', 'expense')->first()?->id ?? $kasAccount->id,
                        'debit' => 2500000,
                        'credit' => 0,
                        'description' => 'Beban bahan kimia',
                    ],
                    [
                        'account_id' => $kasAccount->id,
                        'debit' => 0,
                        'credit' => 2500000,
                        'description' => 'Pembayaran tunai',
                    ],
                ],
            ]);
            $this->command->info('Journal 3 created (draft): ' . $journal3->reference);
        } catch (\Exception $e) {
            $this->command->error('Error creating journal 3: ' . $e->getMessage());
        }

        $this->command->info('Journal seeder completed successfully!');
    }
}
