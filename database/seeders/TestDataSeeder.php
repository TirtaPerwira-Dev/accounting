<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Company;
use App\Models\AccountingStandard;
use App\Models\Journal;
use App\Models\JournalDetail;
use App\Models\Kelompok;
use App\Models\Rekening;
use App\Models\NomorBantu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Generating test data for PDAM Accounting System...');

        // 1. Create Test Users
        $this->command->info('� Creating test users...');
        $users = User::factory(10)->create([
            'password' => Hash::make('password'),
        ]);
        $this->command->info('✅ Users created successfully');

        // 2. Create Test Companies (PDAM Entities)
        $this->command->info('🏢 Creating test PDAM companies...');
        $companies = Company::factory(5)->create();
        $this->command->info('✅ Companies created successfully');

        // 3. Create Sample Journals for each Company
        $this->command->info('� Creating sample journals...');
        foreach ($companies->take(3) as $company) {
            // Get some accounts for this company
            $accounts = NomorBantu::where('kel', 1)->take(10)->get();

            if ($accounts->count() >= 2) {
                for ($i = 0; $i < 20; $i++) {
                    $journal = Journal::create([
                        'company_id' => $company->id,
                        'date' => now()->subDays(rand(1, 30)),
                        'reference' => 'TEST-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                        'description' => 'Test transaction ' . ($i + 1),
                        'total_amount' => $amount = rand(100000, 1000000),
                    ]);

                    // Create balanced journal details
                    $debitAccount = $accounts->random();
                    $creditAccount = $accounts->where('id', '!=', $debitAccount->id)->random();

                    JournalDetail::create([
                        'journal_id' => $journal->id,
                        'account_id' => $debitAccount->id,
                        'description' => 'Debit: ' . $journal->description,
                        'debit' => $amount,
                        'credit' => 0,
                    ]);

                    JournalDetail::create([
                        'journal_id' => $journal->id,
                        'account_id' => $creditAccount->id,
                        'description' => 'Credit: ' . $journal->description,
                        'debit' => 0,
                        'credit' => $amount,
                    ]);
                }
            }
        }
        $this->command->info('✅ Journals created successfully');

        // Summary
        $this->command->info('');
        $this->command->info('📊 Test Data Summary:');
        $this->command->info('👤 Users: ' . User::count());
        $this->command->info('🏢 Companies: ' . Company::count());
        $this->command->info('📋 Accounting Standards: ' . AccountingStandard::count());
        $this->command->info('� Kelompok: ' . Kelompok::count());
        $this->command->info('� Rekening: ' . Rekening::count());
        $this->command->info('📋 Nomor Bantu: ' . NomorBantu::count());
        $this->command->info('📓 Journals: ' . Journal::count());
        $this->command->info('📋 Journal Details: ' . JournalDetail::count());
        $this->command->info('');
        $this->command->info('🎉 Test data generation completed successfully!');
    }
}
