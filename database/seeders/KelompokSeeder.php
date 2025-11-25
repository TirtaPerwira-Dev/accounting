<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Kelompok;
use App\Models\AccountingStandard;

class KelompokSeeder extends Seeder
{
    public function run(): void
    {
        // Get SAKEP standard
        $sakep = AccountingStandard::where('code', 'SAKEP')->first();
        if (!$sakep) {
            $this->command->error('SAKEP accounting standard not found!');
            return;
        }

        $kelompoks = [
            ['no_kel' => '10', 'nama_kel' => 'Aktiva Lancar', 'kel' => '1'],
            ['no_kel' => '20', 'nama_kel' => 'Investasi Jk. Panjang', 'kel' => '1'],
            ['no_kel' => '30', 'nama_kel' => 'Aktiva Tetap', 'kel' => '1'],
            ['no_kel' => '40', 'nama_kel' => 'Aktiva Lain-lain', 'kel' => '1'],
            ['no_kel' => '41', 'nama_kel' => 'Aktiva Lain-lain Berwujud', 'kel' => '1'],
            ['no_kel' => '42', 'nama_kel' => 'Aktiva Tak Berwujud', 'kel' => '1'],
            ['no_kel' => '45', 'nama_kel' => 'Aset Program', 'kel' => '1'],
            ['no_kel' => '50', 'nama_kel' => 'Kewajiban Jk. Pendek', 'kel' => '2'],
            ['no_kel' => '60', 'nama_kel' => 'Kewajiban Jk. Panjang', 'kel' => '2'],
            ['no_kel' => '62', 'nama_kel' => 'Kewajiban Lain-lain', 'kel' => '2'],
            ['no_kel' => '70', 'nama_kel' => 'Modal dan Cadangan', 'kel' => '2'],
            ['no_kel' => '80', 'nama_kel' => 'Pendapatan', 'kel' => '3'],
            ['no_kel' => '88', 'nama_kel' => 'Pendapatan Diluar Usaha', 'kel' => '3'],
            ['no_kel' => '91', 'nama_kel' => 'Biaya Sumber Air', 'kel' => '4'],
            ['no_kel' => '92', 'nama_kel' => 'Biaya pengolahan Air', 'kel' => '4'],
            ['no_kel' => '93', 'nama_kel' => 'Biaya Transmisi dan Distribusi', 'kel' => '4'],
            ['no_kel' => '94', 'nama_kel' => 'Biaya Air Limbah', 'kel' => '4'],
            ['no_kel' => '96', 'nama_kel' => 'Biaya Administrasi dan Umum', 'kel' => '5'],
            ['no_kel' => '98', 'nama_kel' => 'Biaya Diluar Usaha', 'kel' => '6'],
            ['no_kel' => '99', 'nama_kel' => 'Kerugian Luar Biasa', 'kel' => '6'],
        ];

        foreach ($kelompoks as $kelompok) {
            Kelompok::updateOrCreate(
                [
                    'standard_id' => $sakep->id,
                    'no_kel' => $kelompok['no_kel']
                ],
                [
                    'nama_kel' => $kelompok['nama_kel'],
                    'kel' => $kelompok['kel'],
                    'is_active' => true
                ]
            );
        }

        $total = Kelompok::where('standard_id', $sakep->id)->count();
        $this->command->info("Kelompok seeder completed! Created {$total} kelompok records.");
    }
}
