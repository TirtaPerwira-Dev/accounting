<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Kelompok;
use App\Models\Rekening;
use App\Models\AccountingStandard;

class RekeningSeeder extends Seeder
{
    public function run(): void
    {
        // Get SAKEP standard
        $sakep = AccountingStandard::where('code', 'SAKEP')->first();
        if (!$sakep) {
            $this->command->error('SAKEP accounting standard not found!');
            return;
        }

        // Get kelompoks for reference
        $kelompoks = Kelompok::where('standard_id', $sakep->id)->pluck('id', 'no_kel');

        $rekenings = [
            // Kelompok 10 - Aktiva Lancar
            ['kelompok_no' => '10', 'no_rek' => '1101', 'nama_rek' => 'Kas', 'kode' => 'D'],
            ['kelompok_no' => '10', 'no_rek' => '1102', 'nama_rek' => 'Bank', 'kode' => 'D'],
            ['kelompok_no' => '10', 'no_rek' => '1201', 'nama_rek' => 'Deposito', 'kode' => 'D'],
            ['kelompok_no' => '10', 'no_rek' => '1202', 'nama_rek' => 'Surat Berharga', 'kode' => 'D'],
            ['kelompok_no' => '10', 'no_rek' => '1301', 'nama_rek' => 'Piutang Rekening Air', 'kode' => 'D'],
            ['kelompok_no' => '10', 'no_rek' => '1302', 'nama_rek' => 'Piutang Rekening Non Air', 'kode' => 'D'],
            ['kelompok_no' => '10', 'no_rek' => '1303', 'nama_rek' => 'Piutang Kemitraan', 'kode' => 'D'],
            ['kelompok_no' => '10', 'no_rek' => '1304', 'nama_rek' => 'Piutang Rekening Air Limbah', 'kode' => 'D'],
            ['kelompok_no' => '10', 'no_rek' => '1305', 'nama_rek' => 'Piutang Ragu-ragu Air', 'kode' => 'D'],
            ['kelompok_no' => '10', 'no_rek' => '1306', 'nama_rek' => 'Piutang Ragu-ragu Non Air', 'kode' => 'D'],
            ['kelompok_no' => '10', 'no_rek' => '1307', 'nama_rek' => 'Piutang Pegawai', 'kode' => 'D'],
            ['kelompok_no' => '10', 'no_rek' => '1308', 'nama_rek' => 'Piutang AMDK', 'kode' => 'D'],
            ['kelompok_no' => '10', 'no_rek' => '1309', 'nama_rek' => 'Penyisihan Piutang Usaha', 'kode' => 'K'],
            ['kelompok_no' => '10', 'no_rek' => '1401', 'nama_rek' => 'Tagihan Non Usaha', 'kode' => 'D'],
            ['kelompok_no' => '10', 'no_rek' => '1402', 'nama_rek' => 'Piutang Pajak', 'kode' => 'D'],
            ['kelompok_no' => '10', 'no_rek' => '1403', 'nama_rek' => 'Pendapatan Yang Belum Diterima', 'kode' => 'D'],
            ['kelompok_no' => '10', 'no_rek' => '1409', 'nama_rek' => 'Rupa-rupa Piutang Lainnya', 'kode' => 'D'],
            ['kelompok_no' => '10', 'no_rek' => '1501', 'nama_rek' => 'Persediaan Bahan Operasi Kimia', 'kode' => 'D'],
            ['kelompok_no' => '10', 'no_rek' => '1502', 'nama_rek' => 'Persd. Bahan Operasi Lainnya', 'kode' => 'D'],
            ['kelompok_no' => '10', 'no_rek' => '1503', 'nama_rek' => 'Persediaan Pipa & Accesories', 'kode' => 'D'],
            ['kelompok_no' => '10', 'no_rek' => '1509', 'nama_rek' => 'Persediaan Lain-lain', 'kode' => 'D'],
            ['kelompok_no' => '10', 'no_rek' => '1601', 'nama_rek' => 'Biaya Dibayar Dimuka', 'kode' => 'D'],
            ['kelompok_no' => '10', 'no_rek' => '1602', 'nama_rek' => 'Uang Muka Kerja', 'kode' => 'D'],
            ['kelompok_no' => '10', 'no_rek' => '1603', 'nama_rek' => 'Uang Muka Pembelian', 'kode' => 'D'],
            ['kelompok_no' => '10', 'no_rek' => '1604', 'nama_rek' => 'Uang Muka Kepada Kontraktor', 'kode' => 'D'],
            ['kelompok_no' => '10', 'no_rek' => '1605', 'nama_rek' => 'Pembayaran Dimuka Pajak', 'kode' => 'D'],
            ['kelompok_no' => '10', 'no_rek' => '1608', 'nama_rek' => 'Pajak Masukan', 'kode' => 'D'],
            ['kelompok_no' => '10', 'no_rek' => '1609', 'nama_rek' => 'Rupa-rupa Pembayaran Dimuka', 'kode' => 'D'],
            ['kelompok_no' => '10', 'no_rek' => '2001', 'nama_rek' => 'Deposito Berjangka > 1 Tahun', 'kode' => 'D'],


            // Kelompok 20 - Investasi Jangka Panjang
            ['kelompok_no' => '20', 'no_rek' => '2002', 'nama_rek' => 'Penyertaan', 'kode' => 'D'],
            ['kelompok_no' => '20', 'no_rek' => '2003', 'nama_rek' => 'Penanaman Dlm Aktiva Berwujud', 'kode' => 'D'],

            // Kelompok 30 - Aktiva Tetap
            ['kelompok_no' => '30', 'no_rek' => '3101', 'nama_rek' => 'Tanah dan Penyepurnaan Tanah', 'kode' => 'D'],
            ['kelompok_no' => '30', 'no_rek' => '3102', 'nama_rek' => 'Instalasi Sumber Air', 'kode' => 'D'],
            ['kelompok_no' => '30', 'no_rek' => '3103', 'nama_rek' => 'Instalasi Pompa', 'kode' => 'D'],
            ['kelompok_no' => '30', 'no_rek' => '3104', 'nama_rek' => 'Instalasi Pengolahan Air', 'kode' => 'D'],
            ['kelompok_no' => '30', 'no_rek' => '3105', 'nama_rek' => 'Instalasi Transm & Distribusi', 'kode' => 'D'],
            ['kelompok_no' => '30', 'no_rek' => '3106', 'nama_rek' => 'Bangunan/Gedung', 'kode' => 'D'],
            ['kelompok_no' => '30', 'no_rek' => '3107', 'nama_rek' => 'Peralatan dan Perlengkapan', 'kode' => 'D'],
            ['kelompok_no' => '30', 'no_rek' => '3108', 'nama_rek' => 'Kendaraan/Alat Angkut', 'kode' => 'D'],
            ['kelompok_no' => '30', 'no_rek' => '3109', 'nama_rek' => 'Inventaris/Perabot Kantor', 'kode' => 'D'],
            ['kelompok_no' => '30', 'no_rek' => '3110', 'nama_rek' => 'Akm Penystn Inst Sumber', 'kode' => 'K'],
            ['kelompok_no' => '30', 'no_rek' => '3120', 'nama_rek' => 'Akm Penystn Inst Pompa', 'kode' => 'K'],
            ['kelompok_no' => '30', 'no_rek' => '3130', 'nama_rek' => 'Akm Penystn Inst PengolahanAir', 'kode' => 'K'],
            ['kelompok_no' => '30', 'no_rek' => '3140', 'nama_rek' => 'Akm Penystn Inst Trans & Dist', 'kode' => 'K'],
            ['kelompok_no' => '30', 'no_rek' => '3150', 'nama_rek' => 'Akm Penystn Bangunan/Gedung', 'kode' => 'K'],
            ['kelompok_no' => '30', 'no_rek' => '3160', 'nama_rek' => 'Akm Penystn Peralatan&Perlengk', 'kode' => 'K'],
            ['kelompok_no' => '30', 'no_rek' => '3170', 'nama_rek' => 'Akm Penystn Kendaraan', 'kode' => 'K'],
            ['kelompok_no' => '30', 'no_rek' => '3180', 'nama_rek' => 'Akm Penystn Inventaris Kantor', 'kode' => 'K'],
            ['kelompok_no' => '30', 'no_rek' => '3190', 'nama_rek' => 'Akumulasi Penyusutan', 'kode' => 'K'],

            // Kelompok 40 - Aktiva Lainnya
            ['kelompok_no' => '40', 'no_rek' => '4101', 'nama_rek' => 'Aktiva Tetap Dlm Penyelesaian', 'kode' => 'D'],
            ['kelompok_no' => '40', 'no_rek' => '4102', 'nama_rek' => 'Bahan Instalasi', 'kode' => 'D'],
            ['kelompok_no' => '40', 'no_rek' => '4103', 'nama_rek' => 'Uang Jaminan', 'kode' => 'D'],
            ['kelompok_no' => '40', 'no_rek' => '4104', 'nama_rek' => 'Pengeluaran Sementara', 'kode' => 'D'],
            ['kelompok_no' => '40', 'no_rek' => '4105', 'nama_rek' => 'Aktiva Tetap Yg Tdk Berfungsi', 'kode' => 'D'],
            ['kelompok_no' => '40', 'no_rek' => '4106', 'nama_rek' => 'Dana Untuk Pembayaran Utang', 'kode' => 'D'],
            ['kelompok_no' => '40', 'no_rek' => '4107', 'nama_rek' => 'Samb. Baru Yg Akan Diterima', 'kode' => 'D'],
            ['kelompok_no' => '40', 'no_rek' => '4108', 'nama_rek' => 'Pemby. Dimuka kpd Pemerintah', 'kode' => 'D'],

            // Kelompok 42 - Aktiva Tidak Berwujud
            ['kelompok_no' => '42', 'no_rek' => '4201', 'nama_rek' => 'Beban Ditangguhkan', 'kode' => 'D'],
            ['kelompok_no' => '42', 'no_rek' => '4202', 'nama_rek' => 'Akm. Amort. Beban Ditanggukan', 'kode' => 'K'],
            ['kelompok_no' => '42', 'no_rek' => '4203', 'nama_rek' => 'Trade Mark', 'kode' => 'D'],
            ['kelompok_no' => '42', 'no_rek' => '4204', 'nama_rek' => 'Akm. Amortisasi Trade Mark', 'kode' => 'K'],
            ['kelompok_no' => '42', 'no_rek' => '4205', 'nama_rek' => 'Good Will', 'kode' => 'D'],
            ['kelompok_no' => '42', 'no_rek' => '4206', 'nama_rek' => 'Akm. Amortisasi Good Will', 'kode' => 'K'],
            ['kelompok_no' => '42', 'no_rek' => '4207', 'nama_rek' => 'Dokumen Berusia 2 tahun', 'kode' => 'D'],
            ['kelompok_no' => '42', 'no_rek' => '4208', 'nama_rek' => 'Dokumen Berusia 3 tahun', 'kode' => 'D'],
            ['kelompok_no' => '42', 'no_rek' => '4209', 'nama_rek' => 'Dokumen Berusia 4 tahun', 'kode' => 'D'],
            ['kelompok_no' => '42', 'no_rek' => '4210', 'nama_rek' => 'Dokumen Berusia 5 tahun', 'kode' => 'D'],
            ['kelompok_no' => '42', 'no_rek' => '4211', 'nama_rek' => 'Akm.Amortisasi Dokumen 2 thn', 'kode' => 'D'],
            ['kelompok_no' => '42', 'no_rek' => '4212', 'nama_rek' => 'Akm.Amortisasi Dokumen 3 thn', 'kode' => 'D'],
            ['kelompok_no' => '42', 'no_rek' => '4213', 'nama_rek' => 'Akm.Amortisasi Dokumen 4 thn', 'kode' => 'D'],
            ['kelompok_no' => '42', 'no_rek' => '4214', 'nama_rek' => 'Akm.Amortisasi Dokumen 5 thn', 'kode' => 'D'],

            // Kelompok 45 -  Aset Program
            ['kelompok_no' => '45', 'no_rek' => '4501', 'nama_rek' => 'Aset Program Dapenma', 'kode' => 'D'],

            // Kelompok 50 - Kewajiban Jangka Pendek
            ['kelompok_no' => '50', 'no_rek' => '5001', 'nama_rek' => 'Hutang Usaha', 'kode' => 'K'],
            ['kelompok_no' => '50', 'no_rek' => '5002', 'nama_rek' => 'Utang Lain-Lain', 'kode' => 'K'],
            ['kelompok_no' => '50', 'no_rek' => '5003', 'nama_rek' => 'Biaya Masih Harus Dibayar', 'kode' => 'K'],
            ['kelompok_no' => '50', 'no_rek' => '5004', 'nama_rek' => 'Pendapatan Diterima Dimuka', 'kode' => 'K'],
            ['kelompok_no' => '50', 'no_rek' => '5005', 'nama_rek' => 'Pinjaman Jk. Pendek', 'kode' => 'K'],
            ['kelompok_no' => '50', 'no_rek' => '5006', 'nama_rek' => 'Utang Pajak', 'kode' => 'K'],
            ['kelompok_no' => '50', 'no_rek' => '5007', 'nama_rek' => 'Bagian Utang jk. Pjg J.T.', 'kode' => 'K'],
            ['kelompok_no' => '50', 'no_rek' => '5008', 'nama_rek' => 'Beban Bunga & Denda YMH Dibyr', 'kode' => 'K'],
            ['kelompok_no' => '50', 'no_rek' => '5009', 'nama_rek' => 'Utang Bunga Rescheduling', 'kode' => 'K'],
            ['kelompok_no' => '50', 'no_rek' => '5010', 'nama_rek' => 'Jaminan Masa Pemeliharaan', 'kode' => 'K'],
            ['kelompok_no' => '50', 'no_rek' => '5011', 'nama_rek' => 'KIK Jangka Pendek', 'kode' => 'K'],
            ['kelompok_no' => '50', 'no_rek' => '5099', 'nama_rek' => 'Kewajiban Jk. Pendek Lainnya', 'kode' => 'K'],

            // Kelompok 60 - Kewajiban Jangka Panjang
            ['kelompok_no' => '60', 'no_rek' => '6101', 'nama_rek' => 'Pinjaman Dalam Negeri', 'kode' => 'K'],
            ['kelompok_no' => '60', 'no_rek' => '6102', 'nama_rek' => 'Pinjaman Luar Negeri', 'kode' => 'K'],
            ['kelompok_no' => '60', 'no_rek' => '6103', 'nama_rek' => 'Bunga Masa Tenggang', 'kode' => 'K'],
            ['kelompok_no' => '60', 'no_rek' => '6104', 'nama_rek' => 'Utang Leasing', 'kode' => 'K'],

            // Kelompok 62 - Kewajiban Lain-lain
            ['kelompok_no' => '62', 'no_rek' => '6201', 'nama_rek' => 'Pendapatan Yang Ditangguhkan', 'kode' => 'K'],
            ['kelompok_no' => '62', 'no_rek' => '6202', 'nama_rek' => 'Cadangan Dana Meter', 'kode' => 'K'],
            ['kelompok_no' => '62', 'no_rek' => '6203', 'nama_rek' => 'Cadangan Dana', 'kode' => 'K'],
            ['kelompok_no' => '62', 'no_rek' => '6209', 'nama_rek' => 'KIK Jangka Panjang', 'kode' => 'K'],
            ['kelompok_no' => '62', 'no_rek' => '6204', 'nama_rek' => 'Rupa-Rupa Kewajiban Lainnya', 'kode' => 'K'],

            // Kelompok 70 - Ekuitas
            ['kelompok_no' => '70', 'no_rek' => '7001', 'nama_rek' => 'Kekayaan Pemda Yg Dipisahkan', 'kode' => 'K'],
            ['kelompok_no' => '70', 'no_rek' => '7002', 'nama_rek' => 'Penyert Pemert Blm Ttp Status', 'kode' => 'K'],
            ['kelompok_no' => '70', 'no_rek' => '7003', 'nama_rek' => 'Modal', 'kode' => 'K'],
            ['kelompok_no' => '70', 'no_rek' => '7004', 'nama_rek' => 'Modal Hibah', 'kode' => 'K'],
            ['kelompok_no' => '70', 'no_rek' => '7005', 'nama_rek' => 'Selisih Penilaian Kembali', 'kode' => 'K'],
            ['kelompok_no' => '70', 'no_rek' => '7006', 'nama_rek' => 'Cadangan', 'kode' => 'K'],
            ['kelompok_no' => '70', 'no_rek' => '7007', 'nama_rek' => 'Akm L/(R) s/d Th 2008', 'kode' => 'K'],
            ['kelompok_no' => '70', 'no_rek' => '7008', 'nama_rek' => 'Rekening Tampungan Dapenma', 'kode' => 'K'],
            ['kelompok_no' => '70', 'no_rek' => '7009', 'nama_rek' => 'Pengukuran Kembali KIK (OCI)', 'kode' => 'K'],
            ['kelompok_no' => '70', 'no_rek' => '7011', 'nama_rek' => 'Laba (Rugi) Ditahan Th 2009', 'kode' => 'K'],
            ['kelompok_no' => '70', 'no_rek' => '7012', 'nama_rek' => 'Laba/Rugi Bulan Berjalan', 'kode' => 'K'],
            ['kelompok_no' => '70', 'no_rek' => '7013', 'nama_rek' => 'Laba Rugi Bulan Lalu', 'kode' => 'K'],
            ['kelompok_no' => '70', 'no_rek' => '7014', 'nama_rek' => 'Laba Rugi Tahun Lalu', 'kode' => 'K'],
            ['kelompok_no' => '70', 'no_rek' => '7015', 'nama_rek' => 'Laba Rugi Ditahan Tahun 2012', 'kode' => 'K'],
            ['kelompok_no' => '70', 'no_rek' => '7016', 'nama_rek' => 'Laba Rugi th 2013', 'kode' => 'K'],
            ['kelompok_no' => '70', 'no_rek' => '7017', 'nama_rek' => 'Penyertaan Modal ke AMDK', 'kode' => 'K'],

            // Kelompok 80 - Pendapatan
            ['kelompok_no' => '80', 'no_rek' => '8101', 'nama_rek' => 'Pendapatan Penjualan Air', 'kode' => 'K'],
            ['kelompok_no' => '80', 'no_rek' => '8102', 'nama_rek' => 'Pendapatan Non Air', 'kode' => 'K'],
            ['kelompok_no' => '80', 'no_rek' => '8103', 'nama_rek' => 'Pendapatan Kemitraan', 'kode' => 'K'],
            ['kelompok_no' => '80', 'no_rek' => '8104', 'nama_rek' => 'Pendapatan Air Limbah', 'kode' => 'K'],

            // Kelompok 88 - Pendapatan Diluar Usaha
            ['kelompok_no' => '88', 'no_rek' => '8801', 'nama_rek' => 'Pendapatan Lain-lain', 'kode' => 'K'],
            ['kelompok_no' => '88', 'no_rek' => '8901', 'nama_rek' => 'Keuntungan Luar Biasa', 'kode' => 'K'],

            // Kelompok 91 - Biaya Sumber Air
            ['kelompok_no' => '91', 'no_rek' => '9101', 'nama_rek' => 'Biaya Operasi Sumber Air', 'kode' => 'D'],
            ['kelompok_no' => '91', 'no_rek' => '9102', 'nama_rek' => 'Biaya Pemeliharaan Sumber Air', 'kode' => 'D'],
            ['kelompok_no' => '91', 'no_rek' => '9103', 'nama_rek' => 'Biaya Air Baku', 'kode' => 'D'],
            ['kelompok_no' => '91', 'no_rek' => '9109', 'nama_rek' => 'Biaya Penyusutan Sumber Air', 'kode' => 'D'],

            // Kelompok 92 - Biaya Pengolahan Air
            ['kelompok_no' => '92', 'no_rek' => '9201', 'nama_rek' => 'Biaya Opr. Pengolahan Air', 'kode' => 'D'],
            ['kelompok_no' => '92', 'no_rek' => '9202', 'nama_rek' => 'Biaya Pemel. Pengolahan Air', 'kode' => 'D'],
            ['kelompok_no' => '92', 'no_rek' => '9203', 'nama_rek' => 'Biaya Pencadangan Air Curah', 'kode' => 'D'],
            ['kelompok_no' => '92', 'no_rek' => '9209', 'nama_rek' => 'Biaya Peny. Pengolahan Air', 'kode' => 'D'],

            // Kelompok 93 - Biaya Transmisi dan Distribusi
            ['kelompok_no' => '93', 'no_rek' => '9301', 'nama_rek' => 'Biaya Opr. Transm. & Distr.', 'kode' => 'D'],
            ['kelompok_no' => '93', 'no_rek' => '9302', 'nama_rek' => 'Biaya Peml. Trans. & Distr.', 'kode' => 'D'],
            ['kelompok_no' => '93', 'no_rek' => '9309', 'nama_rek' => 'Biaya Peny. Trans. & Distr.', 'kode' => 'D'],

            // Kelompok 96 - Biaya Administrasi dan Umum
            ['kelompok_no' => '96', 'no_rek' => '9601', 'nama_rek' => 'Biaya Pegawai', 'kode' => 'D'],
            ['kelompok_no' => '96', 'no_rek' => '9602', 'nama_rek' => 'Biaya Kantor', 'kode' => 'D'],
            ['kelompok_no' => '96', 'no_rek' => '9603', 'nama_rek' => 'Biaya Hubungan Langganan', 'kode' => 'D'],
            ['kelompok_no' => '96', 'no_rek' => '9604', 'nama_rek' => 'Biaya Penel. & Pengembangan', 'kode' => 'D'],
            ['kelompok_no' => '96', 'no_rek' => '9605', 'nama_rek' => 'Biaya Keuangan', 'kode' => 'D'],
            ['kelompok_no' => '96', 'no_rek' => '9606', 'nama_rek' => 'Biaya Pemeliharaan', 'kode' => 'D'],
            ['kelompok_no' => '96', 'no_rek' => '9607', 'nama_rek' => 'Biaya Penys. & Pengh. Piutang', 'kode' => 'D'],
            ['kelompok_no' => '96', 'no_rek' => '9608', 'nama_rek' => 'Rupa-rupa Biaya Umum', 'kode' => 'D'],
            ['kelompok_no' => '96', 'no_rek' => '9609', 'nama_rek' => 'Biaya Penyusutan', 'kode' => 'D'],
            ['kelompok_no' => '96', 'no_rek' => '9691', 'nama_rek' => 'Biaya Penyusutan', 'kode' => 'D'],
            ['kelompok_no' => '96', 'no_rek' => '9692', 'nama_rek' => 'Biaya Amortisasi', 'kode' => 'D'],

            // Kelompok 98 - Biaya Diluar Usaha
            ['kelompok_no' => '98', 'no_rek' => '9801', 'nama_rek' => 'Biaya Lain-lain', 'kode' => 'D'],

            // Kelompok 99 - Kerugian Luar Biasa
            ['kelompok_no' => '99', 'no_rek' => '9901', 'nama_rek' => 'Kerugian Luar Biasa', 'kode' => 'D'],
        ];

        foreach ($rekenings as $rekening) {
            $kelompokId = $kelompoks[$rekening['kelompok_no']] ?? null;
            if (!$kelompokId) {
                $this->command->warn("Kelompok {$rekening['kelompok_no']} not found for rekening {$rekening['no_rek']}");
                continue;
            }

            Rekening::updateOrCreate(
                [
                    'kelompok_id' => $kelompokId,
                    'no_rek' => $rekening['no_rek']
                ],
                [
                    'nama_rek' => $rekening['nama_rek'],
                    'kode' => $rekening['kode'],
                    'is_active' => true
                ]
            );
        }

        $total = Rekening::count();
        $this->command->info("Rekening seeder completed! Created {$total} rekening records.");
    }
}
