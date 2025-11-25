<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Company;
use App\Models\ChartOfAccount;
use App\Models\Journal;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class QuickTestDataSeeder extends Seeder
{
    /**
     * Generate quick test data for immediate testing
     */
    public function run(): void
    {
        $this->command->info('⚡ Generating quick test data...');

        // Create test users with different roles
        $testUsers = [
            ['name' => 'Direktur PDAM', 'email' => 'direktur@pdam.test', 'role' => 'super_admin'],
            ['name' => 'Kepala Akuntansi', 'email' => 'akuntan@pdam.test', 'role' => 'akuntan'],
            ['name' => 'Staff Kasir', 'email' => 'kasir@pdam.test', 'role' => 'kasir'],
        ];

        foreach ($testUsers as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                ]
            );
        }

        // Create sample companies
        $companies = Company::factory(10)->create();

        // Create sample accounts for each company
        foreach ($companies as $company) {
            // Create basic PDAM accounts
            $basicAccounts = [
                ['code' => '1-10001', 'name' => 'Kas di Tangan', 'type' => 'asset', 'normal_balance' => 'debit'],
                ['code' => '1-10002', 'name' => 'Kas di Bank', 'type' => 'asset', 'normal_balance' => 'debit'],
                ['code' => '1-11001', 'name' => 'Piutang Usaha Air', 'type' => 'asset', 'normal_balance' => 'debit'],
                ['code' => '2-10001', 'name' => 'Utang Usaha', 'type' => 'liability', 'normal_balance' => 'credit'],
                ['code' => '2-20001', 'name' => 'PPN Keluaran', 'type' => 'liability', 'normal_balance' => 'credit'],
                ['code' => '3-10001', 'name' => 'Modal Pemda', 'type' => 'equity', 'normal_balance' => 'credit'],
                ['code' => '4-10001', 'name' => 'Pendapatan Air RT', 'type' => 'revenue', 'normal_balance' => 'credit'],
                ['code' => '4-10002', 'name' => 'Pendapatan Air Niaga', 'type' => 'revenue', 'normal_balance' => 'credit'],
                ['code' => '5-10001', 'name' => 'Beban Air Baku', 'type' => 'expense', 'normal_balance' => 'debit'],
                ['code' => '5-10002', 'name' => 'Beban Listrik', 'type' => 'expense', 'normal_balance' => 'debit'],
            ];

            foreach ($basicAccounts as $accountData) {
                ChartOfAccount::create([
                    'company_id' => $company->id,
                    'code' => $accountData['code'],
                    'name' => $accountData['name'],
                    'type' => $accountData['type'],
                    'normal_balance' => $accountData['normal_balance'],
                    'opening_debit' => $accountData['normal_balance'] === 'debit' ? rand(1000000, 10000000) : 0,
                    'opening_credit' => $accountData['normal_balance'] === 'credit' ? rand(1000000, 10000000) : 0,
                    'is_active' => true,
                ]);
            }

            // Create sample journals for this company
            Journal::factory(20)->create(['company_id' => $company->id]);
        }

        $this->command->info('✅ Quick test data created successfully!');
        $this->command->info('📧 Test login credentials:');
        $this->command->info('   direktur@pdam.test / password');
        $this->command->info('   akuntan@pdam.test / password');
        $this->command->info('   kasir@pdam.test / password');
    }
}
