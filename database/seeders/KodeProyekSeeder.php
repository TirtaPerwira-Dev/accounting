<?php

namespace Database\Seeders;

use App\Models\KodeProyek;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class KodeProyekSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil user pertama sebagai default, jika tidak ada buat user admin
        $defaultUser = User::first();
        if (!$defaultUser) {
            $defaultUser = User::create([
                'name' => 'Administrator',
                'email' => 'admin@pdam.local',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]);
        }

        $kodeProyeks = [
            ['kode' => '01', 'name' => 'Kantor Pusat', 'tahun' => 2012, 'ket' => ''],
            ['kode' => '02', 'name' => 'Unit IKK Bukateja', 'tahun' => 2012, 'ket' => ''],
            ['kode' => '03', 'name' => 'Unit IKK Bobotsari', 'tahun' => 2012, 'ket' => ''],
            ['kode' => '04', 'name' => 'Unit IKK Kutasari', 'tahun' => 2012, 'ket' => ''],
            ['kode' => '05', 'name' => 'Unit IKK Bojongsari', 'tahun' => 2012, 'ket' => ''],
            ['kode' => '06', 'name' => 'Unit IKK Mrebet', 'tahun' => 2012, 'ket' => ''],
            ['kode' => '07', 'name' => 'Unit IKK Kemangkon', 'tahun' => 2012, 'ket' => ''],
            ['kode' => '08', 'name' => 'Unit IKK Kejobong', 'tahun' => 2012, 'ket' => ''],
            ['kode' => '09', 'name' => 'Unit IKK Rembang', 'tahun' => 2012, 'ket' => ''],
            ['kode' => '10', 'name' => 'Unit AMDK', 'tahun' => 2012, 'ket' => ''],
            ['kode' => '00', 'name' => 'Konsolidasi', 'tahun' => 2012, 'ket' => ''],
            ['kode' => '11', 'name' => 'Pencucian Kendaraan', 'tahun' => 2012, 'ket' => ''],
            ['kode' => '12', 'name' => 'Unit IKK Padamara', 'tahun' => 2012, 'ket' => ''],
            ['kode' => '13', 'name' => 'Unit IKK Kalimanah', 'tahun' => 2012, 'ket' => ''],
            ['kode' => '14', 'name' => 'Unit IKK Karang Reja', 'tahun' => 2012, 'ket' => ''],
            ['kode' => '15', 'name' => 'IKK Kaligondang', 'tahun' => 2012, 'ket' => ''],
            ['kode' => '16', 'name' => 'Cabang Kota', 'tahun' => 2014, 'ket' => ''],
            ['kode' => '17', 'name' => 'Cabang Usman Janatin', 'tahun' => 2022, 'ket' => ''],
            ['kode' => '18', 'name' => 'Cabang Jendral Sudirman', 'tahun' => 2022, 'ket' => ''],
        ];

        foreach ($kodeProyeks as $data) {
            KodeProyek::create([
                'kode' => $data['kode'],
                'name' => $data['name'],
                'tahun' => $data['tahun'],
                'ket' => $data['ket'] ?: null,
                'user_id' => $defaultUser->id,
            ]);
        }

        $this->command->info('KodeProyek seeder completed successfully.');
    }
}
