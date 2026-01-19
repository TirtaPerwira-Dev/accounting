<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SaldoAwalRekening;
use App\Models\Rekening;

class SaldoAwalRekeningSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tahun = now()->year;

        // Contoh saldo awal untuk beberapa rekening penting
        $saldoAwalData = [
            // Kas (Debit)
            ['no_kel' => 10, 'no_rek' => 1101, 'saldo' => 50000000, 'posisi' => 'D', 'keterangan' => 'Saldo awal kas besar'],
            
            // Bank BPD (Debit)
            ['no_kel' => 10, 'no_rek' => 1102, 'saldo' => 150000000, 'posisi' => 'D', 'keterangan' => 'Saldo awal bank BPD'],
            
            // Piutang Rekening Air (Debit)
            ['no_kel' => 10, 'no_rek' => 1301, 'saldo' => 75000000, 'posisi' => 'D', 'keterangan' => 'Saldo awal piutang pelanggan air'],
            
            // Utang Usaha (Kredit)
            ['no_kel' => 50, 'no_rek' => 5001, 'saldo' => 30000000, 'posisi' => 'K', 'keterangan' => 'Saldo awal hutang supplier'],
            
            // Modal (Kredit)
            ['no_kel' => 70, 'no_rek' => 7003, 'saldo' => 245000000, 'posisi' => 'K', 'keterangan' => 'Saldo awal modal usaha'],
        ];

        foreach ($saldoAwalData as $data) {
            // Cari rekening berdasarkan no_kel dan no_rek
            $rekening = Rekening::whereHas('kelompok', function ($q) use ($data) {
                $q->where('no_kel', $data['no_kel']);
            })->where('no_rek', $data['no_rek'])->first();

            if ($rekening) {
                SaldoAwalRekening::updateOrCreate(
                    [
                        'tahun' => $tahun,
                        'rekening_id' => $rekening->id,
                        'nomor_bantu_id' => null,
                    ],
                    [
                        'saldo_awal' => $data['saldo'],
                        'posisi' => $data['posisi'],
                        'keterangan' => $data['keterangan'],
                    ]
                );
            }
        }

        $this->command->info('✅ Saldo awal rekening berhasil di-seed untuk tahun ' . $tahun);
        $this->command->info('Total: ' . count($saldoAwalData) . ' rekening');
    }
}
