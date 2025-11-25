<?php

namespace Database\Seeders;

use App\Models\AccountingStandard;
use App\Models\Kelompok;
use App\Models\Rekening;
use App\Models\NomorBantu;
use Illuminate\Database\Seeder;

class SakepDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🏗️ Seeding SAKEP Chart of Accounts data...');

        // 1. Pastikan ada SAKEP accounting standard
        $sakepStandard = AccountingStandard::firstOrCreate([
            'code' => 'SAKEP',
        ], [
            'name' => 'Standar Akuntansi Keuangan Entitas Privat (SAKEP)',
            'version' => '2024',
            'description' => 'Standar akuntansi untuk PDAM dan entitas privat lainnya di Indonesia',
            'is_active' => true,
        ]);

        $this->command->info('📋 SAKEP Standard: ' . $sakepStandard->name);

        // 2. Data Kelompok
        $kelompokData = [
            ['no_kel' => 10, 'nama_kel' => 'Aktiva Lancar', 'kel' => 1],
            ['no_kel' => 20, 'nama_kel' => 'Investasi Jk. Panjang', 'kel' => 1],
            ['no_kel' => 30, 'nama_kel' => 'Aktiva Tetap', 'kel' => 1],
            ['no_kel' => 40, 'nama_kel' => 'Aktiva Lain-lain', 'kel' => 1],
            ['no_kel' => 41, 'nama_kel' => 'Aktiva Lain-lain Berwujud', 'kel' => 1],
            ['no_kel' => 42, 'nama_kel' => 'Aktiva Tak Berwujud', 'kel' => 1],
            ['no_kel' => 45, 'nama_kel' => 'Aset Program', 'kel' => 1],
            ['no_kel' => 50, 'nama_kel' => 'Kewajiban Jk. Pendek', 'kel' => 2],
            ['no_kel' => 60, 'nama_kel' => 'Kewajiban Jk. Panjang', 'kel' => 2],
            ['no_kel' => 62, 'nama_kel' => 'Kewajiban Lain-lain', 'kel' => 2],
            ['no_kel' => 70, 'nama_kel' => 'Modal dan Cadangan', 'kel' => 2],
            ['no_kel' => 80, 'nama_kel' => 'Pendapatan', 'kel' => 3],
            ['no_kel' => 88, 'nama_kel' => 'Pendapatan Diluar Usaha', 'kel' => 3],
            ['no_kel' => 91, 'nama_kel' => 'Biaya Sumber Air', 'kel' => 4],
            ['no_kel' => 92, 'nama_kel' => 'Biaya Pengolahan Air', 'kel' => 4],
            ['no_kel' => 93, 'nama_kel' => 'Biaya Transmisi dan Distribusi', 'kel' => 4],
            ['no_kel' => 94, 'nama_kel' => 'Biaya Air Limbah', 'kel' => 4],
            ['no_kel' => 96, 'nama_kel' => 'Biaya Administrasi dan Umum', 'kel' => 5],
            ['no_kel' => 98, 'nama_kel' => 'Biaya Diluar Usaha', 'kel' => 6],
            ['no_kel' => 99, 'nama_kel' => 'Kerugian Luar Biasa', 'kel' => 6],
        ];

        $this->command->info('📝 Creating Kelompok...');
        foreach ($kelompokData as $data) {
            Kelompok::firstOrCreate([
                'standard_id' => $sakepStandard->id,
                'no_kel' => $data['no_kel'],
            ], [
                'nama_kel' => $data['nama_kel'],
                'kel' => $data['kel'],
                'is_active' => true,
            ]);
        }

        // 3. Data Rekening (sample data dari list yang Anda berikan)
        $rekeningData = [
            ['no_kel' => 10, 'no_rek' => 1101, 'nama_rek' => 'Kas', 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1102, 'nama_rek' => 'Bank', 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1201, 'nama_rek' => 'Deposito', 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1202, 'nama_rek' => 'Surat Berharga', 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1301, 'nama_rek' => 'Piutang Rekening Air', 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1302, 'nama_rek' => 'Piutang Rekening Non Air', 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1303, 'nama_rek' => 'Piutang Kemitraan', 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1304, 'nama_rek' => 'Piutang Rekening Air Limbah', 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1305, 'nama_rek' => 'Piutang Ragu-ragu Air', 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1306, 'nama_rek' => 'Piutang Ragu-ragu Non Air', 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1307, 'nama_rek' => 'Piutang Pegawai', 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1308, 'nama_rek' => 'Piutang AMDK', 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1309, 'nama_rek' => 'Penyisihan Piutang Usaha', 'kode' => 'K'],
            ['no_kel' => 10, 'no_rek' => 1401, 'nama_rek' => 'Tagihan Non Usaha', 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1402, 'nama_rek' => 'Piutang Pajak', 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1403, 'nama_rek' => 'Pendapatan Yang Belum Diterima', 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1409, 'nama_rek' => 'Rupa-rupa Piutang Lainnya', 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1501, 'nama_rek' => 'Persediaan Bahan Operasi Kimia', 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1502, 'nama_rek' => 'Persd. Bahan Operasi Lainnya', 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1503, 'nama_rek' => 'Persediaan Pipa & Accesories', 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1509, 'nama_rek' => 'Persediaan Lain-lain', 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1601, 'nama_rek' => 'Biaya Dibayar Dimuka', 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1602, 'nama_rek' => 'Uang Muka Kerja', 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1603, 'nama_rek' => 'Uang Muka Pembelian', 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1604, 'nama_rek' => 'Uang Muka Kepada Kontraktor', 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1605, 'nama_rek' => 'Pembayaran Dimuka Pajak', 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1608, 'nama_rek' => 'Pajak Masukan', 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1609, 'nama_rek' => 'Rupa-rupa Pembayaran Dimuka', 'kode' => 'D'],

            // Investasi Jangka Panjang
            ['no_kel' => 20, 'no_rek' => 2001, 'nama_rek' => 'Deposito Berjangka > 1 Tahun', 'kode' => 'D'],
            ['no_kel' => 20, 'no_rek' => 2002, 'nama_rek' => 'Penyertaan', 'kode' => 'D'],
            ['no_kel' => 20, 'no_rek' => 2003, 'nama_rek' => 'Penanaman Dlm Aktiva Berwujud', 'kode' => 'D'],

            // Aktiva Tetap
            ['no_kel' => 30, 'no_rek' => 3101, 'nama_rek' => 'Tanah dan Penyepurnaan Tanah', 'kode' => 'D'],
            ['no_kel' => 30, 'no_rek' => 3102, 'nama_rek' => 'Instalasi Sumber Air', 'kode' => 'D'],
            ['no_kel' => 30, 'no_rek' => 3103, 'nama_rek' => 'Instalasi Pompa', 'kode' => 'D'],
            ['no_kel' => 30, 'no_rek' => 3104, 'nama_rek' => 'Instalasi Pengolahan Air', 'kode' => 'D'],
            ['no_kel' => 30, 'no_rek' => 3105, 'nama_rek' => 'Instalasi Transm & Distribusi', 'kode' => 'D'],
            ['no_kel' => 30, 'no_rek' => 3106, 'nama_rek' => 'Bangunan/Gedung', 'kode' => 'D'],
            ['no_kel' => 30, 'no_rek' => 3107, 'nama_rek' => 'Peralatan dan Perlengkapan', 'kode' => 'D'],
            ['no_kel' => 30, 'no_rek' => 3108, 'nama_rek' => 'Kendaraan/Alat Angkut', 'kode' => 'D'],
            ['no_kel' => 30, 'no_rek' => 3109, 'nama_rek' => 'Inventaris/Perabot Kantor', 'kode' => 'D'],
            ['no_kel' => 30, 'no_rek' => 3110, 'nama_rek' => 'Akm Penystn Inst Sumber', 'kode' => 'K'],
            ['no_kel' => 30, 'no_rek' => 3120, 'nama_rek' => 'Akm Penystn Inst Pompa', 'kode' => 'K'],
            ['no_kel' => 30, 'no_rek' => 3130, 'nama_rek' => 'Akm Penystn Inst PengolahanAir', 'kode' => 'K'],
            ['no_kel' => 30, 'no_rek' => 3140, 'nama_rek' => 'Akm Penystn Inst Trans & Dist', 'kode' => 'K'],
            ['no_kel' => 30, 'no_rek' => 3150, 'nama_rek' => 'Akm Penystn Bangunan/Gedung', 'kode' => 'K'],
            ['no_kel' => 30, 'no_rek' => 3160, 'nama_rek' => 'Akm Penystn Peralatan&Perlengk', 'kode' => 'K'],
            ['no_kel' => 30, 'no_rek' => 3170, 'nama_rek' => 'Akm Penystn Kendaraan', 'kode' => 'K'],
            ['no_kel' => 30, 'no_rek' => 3180, 'nama_rek' => 'Akm Penystn Inventaris Kantor', 'kode' => 'K'],
            ['no_kel' => 30, 'no_rek' => 3190, 'nama_rek' => 'Akumulasi Penyusutan', 'kode' => 'K'],

            // Kewajiban Jangka Pendek
            ['no_kel' => 50, 'no_rek' => 5001, 'nama_rek' => 'Hutang Usaha', 'kode' => 'K'],
            ['no_kel' => 50, 'no_rek' => 5002, 'nama_rek' => 'Utang Lain-Lain', 'kode' => 'K'],
            ['no_kel' => 50, 'no_rek' => 5003, 'nama_rek' => 'Biaya Masih Harus Dibayar', 'kode' => 'K'],
            ['no_kel' => 50, 'no_rek' => 5004, 'nama_rek' => 'Pendapatan Diterima Dimuka', 'kode' => 'K'],
            ['no_kel' => 50, 'no_rek' => 5005, 'nama_rek' => 'Pinjaman Jk. Pendek', 'kode' => 'K'],
            ['no_kel' => 50, 'no_rek' => 5006, 'nama_rek' => 'Utang Pajak', 'kode' => 'K'],

            // Modal dan Cadangan
            ['no_kel' => 70, 'no_rek' => 7001, 'nama_rek' => 'Kekayaan Pemda Yg Dipisahkan', 'kode' => 'K'],
            ['no_kel' => 70, 'no_rek' => 7002, 'nama_rek' => 'Penyert Pemert Blm Ttp Status', 'kode' => 'K'],
            ['no_kel' => 70, 'no_rek' => 7003, 'nama_rek' => 'M o d a l', 'kode' => 'K'],
            ['no_kel' => 70, 'no_rek' => 7004, 'nama_rek' => 'Modal Hibah', 'kode' => 'K'],
            ['no_kel' => 70, 'no_rek' => 7011, 'nama_rek' => 'Laba (Rugi) Ditahan Th 2009', 'kode' => 'K'],
            ['no_kel' => 70, 'no_rek' => 7012, 'nama_rek' => 'Laba/Rugi Bulan Berjalan', 'kode' => 'K'],

            // Pendapatan
            ['no_kel' => 80, 'no_rek' => 8101, 'nama_rek' => 'Pendapatan Penjualan Air', 'kode' => 'K'],
            ['no_kel' => 80, 'no_rek' => 8102, 'nama_rek' => 'Pendapatan Non Air', 'kode' => 'K'],
            ['no_kel' => 80, 'no_rek' => 8103, 'nama_rek' => 'Pendapatan Kemitraan', 'kode' => 'K'],
            ['no_kel' => 80, 'no_rek' => 8104, 'nama_rek' => 'Pendapatan Air Limbah', 'kode' => 'K'],

            // Pendapatan Lain-lain
            ['no_kel' => 88, 'no_rek' => 8801, 'nama_rek' => 'Pendapatan Lain-lain', 'kode' => 'K'],
            ['no_kel' => 88, 'no_rek' => 8901, 'nama_rek' => 'Keuntungan Luar Biasa', 'kode' => 'K'],

            // Biaya Sumber Air
            ['no_kel' => 91, 'no_rek' => 9101, 'nama_rek' => 'Biaya Operasi Sumber Air', 'kode' => 'D'],
            ['no_kel' => 91, 'no_rek' => 9102, 'nama_rek' => 'Biaya Pemeliharaan Sumber Air', 'kode' => 'D'],
            ['no_kel' => 91, 'no_rek' => 9103, 'nama_rek' => 'Biaya Air Baku', 'kode' => 'D'],

            // Biaya Pengolahan Air
            ['no_kel' => 92, 'no_rek' => 9201, 'nama_rek' => 'Biaya Opr. Pengolahan Air', 'kode' => 'D'],
            ['no_kel' => 92, 'no_rek' => 9202, 'nama_rek' => 'Biaya Pemel. Pengolahan Air', 'kode' => 'D'],

            // Biaya Transmisi dan Distribusi
            ['no_kel' => 93, 'no_rek' => 9301, 'nama_rek' => 'Biaya Opr. Transm. & Distr.', 'kode' => 'D'],
            ['no_kel' => 93, 'no_rek' => 9302, 'nama_rek' => 'Biaya Peml. Trans. & Distr.', 'kode' => 'D'],

            // Biaya Administrasi dan Umum
            ['no_kel' => 96, 'no_rek' => 9601, 'nama_rek' => 'Biaya Pegawai', 'kode' => 'D'],
            ['no_kel' => 96, 'no_rek' => 9602, 'nama_rek' => 'Biaya Kantor', 'kode' => 'D'],
            ['no_kel' => 96, 'no_rek' => 9603, 'nama_rek' => 'Biaya Hubungan Langganan', 'kode' => 'D'],
            ['no_kel' => 96, 'no_rek' => 9604, 'nama_rek' => 'Biaya Penel. & Pengembangan', 'kode' => 'D'],
            ['no_kel' => 96, 'no_rek' => 9605, 'nama_rek' => 'Biaya Keuangan', 'kode' => 'D'],
            ['no_kel' => 96, 'no_rek' => 9606, 'nama_rek' => 'Biaya Pemeliharaan', 'kode' => 'D'],
            ['no_kel' => 96, 'no_rek' => 9607, 'nama_rek' => 'Biaya Penys. & Pengh. Piutang', 'kode' => 'D'],
            ['no_kel' => 96, 'no_rek' => 9608, 'nama_rek' => 'Rupa-rupa Biaya Umum', 'kode' => 'D'],
            ['no_kel' => 96, 'no_rek' => 9609, 'nama_rek' => 'Biaya Penyusutan', 'kode' => 'D'],
            ['no_kel' => 96, 'no_rek' => 9691, 'nama_rek' => 'Biaya Penyusutan', 'kode' => 'D'],
            ['no_kel' => 96, 'no_rek' => 9692, 'nama_rek' => 'Biaya Amortisasi', 'kode' => 'D'],

            // Biaya Lain-lain
            ['no_kel' => 98, 'no_rek' => 9801, 'nama_rek' => 'Biaya Lain-lain', 'kode' => 'D'],

            // Kerugian Luar Biasa
            ['no_kel' => 99, 'no_rek' => 9901, 'nama_rek' => 'Kerugian Luar Biasa', 'kode' => 'D'],
        ];

        $this->command->info('📝 Creating Rekening...');
        foreach ($rekeningData as $data) {
            $kelompok = Kelompok::where('standard_id', $sakepStandard->id)
                ->where('no_kel', $data['no_kel'])
                ->first();

            if ($kelompok) {
                Rekening::firstOrCreate([
                    'kelompok_id' => $kelompok->id,
                    'no_rek' => $data['no_rek'],
                ], [
                    'nama_rek' => $data['nama_rek'],
                    'kode' => $data['kode'],
                    'is_active' => true,
                ]);
            }
        }

        // 4. Beberapa sample data Nomor Bantu dari data yang Anda berikan
        $nomorBantuData = [
            // Bank details
            ['no_kel' => 10, 'no_rek' => 1102, 'no_bantu' => 20, 'nm_bantu' => 'Bank BPD Capem Pasar Kota', 'kel' => 1, 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1102, 'no_bantu' => 21, 'nm_bantu' => 'Bank BPD Capem Bobotsari', 'kel' => 1, 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1102, 'no_bantu' => 22, 'nm_bantu' => 'BKK/BPR Terminal Purbalingga', 'kel' => 1, 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1102, 'no_bantu' => 23, 'nm_bantu' => 'Bank BPD Cabang', 'kel' => 1, 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1102, 'no_bantu' => 28, 'nm_bantu' => 'BRI Cabang Purbalingga', 'kel' => 1, 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1102, 'no_bantu' => 30, 'nm_bantu' => 'BMT Mrebet', 'kel' => 1, 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1102, 'no_bantu' => 31, 'nm_bantu' => 'BMT Kemangkon', 'kel' => 1, 'kode' => 'D'],

            // Kas details
            ['no_kel' => 10, 'no_rek' => 1101, 'no_bantu' => 10, 'nm_bantu' => 'Kas Besar', 'kel' => 1, 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1101, 'no_bantu' => 11, 'nm_bantu' => 'Kas Kecil Pusat', 'kel' => 1, 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1101, 'no_bantu' => 12, 'nm_bantu' => 'Kas Kecil IKK Bobotsari', 'kel' => 1, 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1101, 'no_bantu' => 13, 'nm_bantu' => 'Kas Kecil IKK Bojongsari', 'kel' => 1, 'kode' => 'D'],

            // Deposito details
            ['no_kel' => 10, 'no_rek' => 1201, 'no_bantu' => 10, 'nm_bantu' => 'Deposito di Bank BPD', 'kel' => 1, 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1201, 'no_bantu' => 20, 'nm_bantu' => 'Deposito di Bank BRI', 'kel' => 1, 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1201, 'no_bantu' => 30, 'nm_bantu' => 'Deposito di Bank Mandiri', 'kel' => 1, 'kode' => 'D'],

            // Piutang Pajak details
            ['no_kel' => 10, 'no_rek' => 1402, 'no_bantu' => 10, 'nm_bantu' => 'Piutang Pajak Penghasilan (21)', 'kel' => 1, 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1402, 'no_bantu' => 20, 'nm_bantu' => 'Uang Muka Pajak Pasal 25', 'kel' => 1, 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1402, 'no_bantu' => 30, 'nm_bantu' => 'Uang Muka Pajak Pasal 22', 'kel' => 1, 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1402, 'no_bantu' => 40, 'nm_bantu' => 'Uang Muka Pajak Pasal 23', 'kel' => 1, 'kode' => 'D'],

            // Pajak Dimuka details
            ['no_kel' => 10, 'no_rek' => 1605, 'no_bantu' => 10, 'nm_bantu' => 'Pajak Dimuka (PPN)', 'kel' => 1, 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1605, 'no_bantu' => 20, 'nm_bantu' => 'Pajak Pasal 23', 'kel' => 1, 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1605, 'no_bantu' => 30, 'nm_bantu' => 'Pajak Pasal 25', 'kel' => 1, 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1605, 'no_bantu' => 40, 'nm_bantu' => 'Pph psl 21', 'kel' => 1, 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1605, 'no_bantu' => 50, 'nm_bantu' => 'Pph psl 26', 'kel' => 1, 'kode' => 'D'],
            ['no_kel' => 10, 'no_rek' => 1605, 'no_bantu' => 60, 'nm_bantu' => 'Pph psl 22', 'kel' => 1, 'kode' => 'D'],

            // Utang Pajak details
            ['no_kel' => 50, 'no_rek' => 5006, 'no_bantu' => 10, 'nm_bantu' => 'Utang Pajak PPN', 'kel' => 2, 'kode' => 'K'],
            ['no_kel' => 50, 'no_rek' => 5006, 'no_bantu' => 20, 'nm_bantu' => 'Utang Pajak Pasal 25', 'kel' => 2, 'kode' => 'K'],
            ['no_kel' => 50, 'no_rek' => 5006, 'no_bantu' => 30, 'nm_bantu' => 'Utang Pajak Pasal 23', 'kel' => 2, 'kode' => 'K'],
            ['no_kel' => 50, 'no_rek' => 5006, 'no_bantu' => 40, 'nm_bantu' => 'Utang Pajak Pasal 21', 'kel' => 2, 'kode' => 'K'],
            ['no_kel' => 50, 'no_rek' => 5006, 'no_bantu' => 50, 'nm_bantu' => 'PPN SR', 'kel' => 2, 'kode' => 'K'],
            ['no_kel' => 50, 'no_rek' => 5006, 'no_bantu' => 60, 'nm_bantu' => 'Utang Pajak Psl 4', 'kel' => 2, 'kode' => 'K'],
            ['no_kel' => 50, 'no_rek' => 5006, 'no_bantu' => 70, 'nm_bantu' => 'Utang Pajak Pasal 29', 'kel' => 2, 'kode' => 'K'],
            ['no_kel' => 50, 'no_rek' => 5006, 'no_bantu' => 80, 'nm_bantu' => 'ppn amdk', 'kel' => 2, 'kode' => 'K'],

            // Biaya yang masih harus dibayar
            ['no_kel' => 50, 'no_rek' => 5003, 'no_bantu' => 20, 'nm_bantu' => 'Biaya Telephon', 'kel' => 2, 'kode' => 'K'],
            ['no_kel' => 50, 'no_rek' => 5003, 'no_bantu' => 30, 'nm_bantu' => 'Biaya gaji ke 13', 'kel' => 2, 'kode' => 'K'],

            // Pendapatan Air
            ['no_kel' => 80, 'no_rek' => 8101, 'no_bantu' => 10, 'nm_bantu' => 'Pendapatan Harga Air', 'kel' => 3, 'kode' => 'K'],
            ['no_kel' => 80, 'no_rek' => 8101, 'no_bantu' => 11, 'nm_bantu' => 'Pendapatan Air Tangk', 'kel' => 3, 'kode' => 'K'],
            ['no_kel' => 80, 'no_rek' => 8101, 'no_bantu' => 12, 'nm_bantu' => 'Penjualan air ke PDAM Pemalang', 'kel' => 3, 'kode' => 'K'],
            ['no_kel' => 80, 'no_rek' => 8101, 'no_bantu' => 13, 'nm_bantu' => 'Pendapatan HU', 'kel' => 3, 'kode' => 'K'],
            ['no_kel' => 80, 'no_rek' => 8101, 'no_bantu' => 14, 'nm_bantu' => 'Pencurian Air', 'kel' => 3, 'kode' => 'K'],
            ['no_kel' => 80, 'no_rek' => 8101, 'no_bantu' => 20, 'nm_bantu' => 'Pendapatan Jasa Administrasi', 'kel' => 3, 'kode' => 'K'],
            ['no_kel' => 80, 'no_rek' => 8101, 'no_bantu' => 99, 'nm_bantu' => 'Penjualan Air Lainnya', 'kel' => 3, 'kode' => 'K'],

            // Pendapatan Non Air
            ['no_kel' => 80, 'no_rek' => 8102, 'no_bantu' => 10, 'nm_bantu' => 'Pendapatan Sambungan Baru', 'kel' => 3, 'kode' => 'K'],
            ['no_kel' => 80, 'no_rek' => 8102, 'no_bantu' => 11, 'nm_bantu' => 'Pendapatan Pendaftaran SR', 'kel' => 3, 'kode' => 'K'],
            ['no_kel' => 80, 'no_rek' => 8102, 'no_bantu' => 12, 'nm_bantu' => 'Biaya Balik Nama', 'kel' => 3, 'kode' => 'K'],
            ['no_kel' => 80, 'no_rek' => 8102, 'no_bantu' => 13, 'nm_bantu' => 'Jaminan Langganan', 'kel' => 3, 'kode' => 'K'],
            ['no_kel' => 80, 'no_rek' => 8102, 'no_bantu' => 20, 'nm_bantu' => 'Pendapatan Sewa Instalasi', 'kel' => 3, 'kode' => 'K'],
            ['no_kel' => 80, 'no_rek' => 8102, 'no_bantu' => 30, 'nm_bantu' => 'Pendapatan Pemeriksaan Air Lab', 'kel' => 3, 'kode' => 'K'],
            ['no_kel' => 80, 'no_rek' => 8102, 'no_bantu' => 40, 'nm_bantu' => 'Pendp. Penyambungan Kembali', 'kel' => 3, 'kode' => 'K'],
            ['no_kel' => 80, 'no_rek' => 8102, 'no_bantu' => 50, 'nm_bantu' => 'Pendpatan REKOMTEK', 'kel' => 3, 'kode' => 'K'],
            ['no_kel' => 80, 'no_rek' => 8102, 'no_bantu' => 60, 'nm_bantu' => 'Pendp. Pemeriksaan Inst. Pelg.', 'kel' => 3, 'kode' => 'K'],
            ['no_kel' => 80, 'no_rek' => 8102, 'no_bantu' => 61, 'nm_bantu' => 'Pndptan Survey Ketrsediaan Air', 'kel' => 3, 'kode' => 'K'],
        ];

        $this->command->info('📝 Creating sample Nomor Bantu...');
        foreach ($nomorBantuData as $data) {
            $rekening = Rekening::whereHas('kelompok', function ($query) use ($data, $sakepStandard) {
                $query->where('standard_id', $sakepStandard->id)
                    ->where('no_kel', $data['no_kel']);
            })->where('no_rek', $data['no_rek'])->first();

            if ($rekening) {
                NomorBantu::firstOrCreate([
                    'rekening_id' => $rekening->id,
                    'no_bantu' => $data['no_bantu'],
                ], [
                    'nm_bantu' => $data['nm_bantu'],
                    'kel' => $data['kel'],
                    'kode' => $data['kode'],
                    'is_active' => true,
                ]);
            }
        }

        // Summary
        $this->command->info('');
        $this->command->info('✅ SAKEP Data Summary:');
        $this->command->info('📋 Kelompok: ' . Kelompok::where('standard_id', $sakepStandard->id)->count());
        $this->command->info('📋 Rekening: ' . Rekening::whereHas('kelompok', function ($query) use ($sakepStandard) {
            $query->where('standard_id', $sakepStandard->id);
        })->count());
        $this->command->info('📋 Nomor Bantu: ' . NomorBantu::whereHas('rekening.kelompok', function ($query) use ($sakepStandard) {
            $query->where('standard_id', $sakepStandard->id);
        })->count());
        $this->command->info('');
        $this->command->info('🎉 SAKEP data seeding completed successfully!');
    }
}
