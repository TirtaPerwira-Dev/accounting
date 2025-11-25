<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\AccountingStandard;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        // Ensure accounting standard exists
        $standard = AccountingStandard::firstOrCreate([
            'code' => 'SAKEP',
        ], [
            'name' => 'Pernyataan Standar Akuntansi Keuangan (PSAK)',
            'description' => 'Indonesian Financial Accounting Standards',
            'is_active' => true,
        ]);

        // Create the main company profile (check by name only)
        $company = Company::where('name', 'Perumdam Tirta Perwira')->first();

        if (!$company) {
            Company::create([
                'name' => 'Perumdam Tirta Perwira',
                'npwp' => '01.234.567.8-901.000',
                'address' => 'Jl. Letnan Jenderal S Parman No.62, Kedung Menjangan, Bancar, Kec. Purbalingga, Kabupaten Purbalingga, Jawa Tengah 53316',
                'phone' => '+62281-891706',
                'accounting_standard_id' => $standard->id,
                'config' => [
                    'ppn_rate' => 11,
                    'currency' => 'IDR',
                    'fiscal_year_start' => '01-01',
                    'efaktur_seri' => '010.001-25.'
                ],
                'is_active' => true,
            ]);

            $this->command->info('Company profile created successfully!');
        } else {
            $this->command->info('Company profile already exists!');
        }
    }
}
