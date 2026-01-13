<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JurnalPembelian;
use App\Models\JurnalRekeningAir;
use App\Models\JurnalRekeningAirDetail;
use App\Models\JurnalPenerimaanKas;
use App\Models\JurnalPenerimaanKasDetail;
use App\Models\JurnalBayarKasBank;
use App\Models\JurnalPemakaianBahan;
use App\Models\JurnalMemorial;
use App\Models\Kelompok;
use App\Models\Rekening;
use App\Models\NomorBantu;
use App\Models\KodeProyek;
use Carbon\Carbon;

class JurnalTestingSeeder extends Seeder
{
    public function run(): void
    {
        echo "🧪 Mulai seeding data testing jurnal...\n\n";

        // Get sample accounts
        $kasBank = NomorBantu::whereHas('rekening', function ($q) {
            $q->where('no_rek', '1102'); // Bank
        })->first();

        $piutangAir = NomorBantu::whereHas('rekening', function ($q) {
            $q->where('no_rek', '1301'); // Piutang Air
        })->first();

        $pendapatanAir = NomorBantu::whereHas('rekening', function ($q) {
            $q->where('no_rek', '8101'); // Pendapatan Air
        })->first();

        $bebanPembelian = NomorBantu::whereHas('rekening', function ($q) {
            $q->where('no_rek', '9101'); // Beban Operasi
        })->first();

        $hutangUsaha = NomorBantu::whereHas('rekening', function ($q) {
            $q->where('no_rek', '5001'); // Hutang Usaha
        })->first();

        $persediaan = NomorBantu::whereHas('rekening', function ($q) {
            $q->where('no_rek', '1501'); // Persediaan
        })->first();

        $kodeProyek = KodeProyek::first();

        // ===== 1. JURNAL PEMBELIAN BARANG (5 data) =====
        echo "📝 1. Seeding Jurnal Pembelian Barang...\n";

        for ($i = 1; $i <= 5; $i++) {
            $tanggal = Carbon::now()->subDays(rand(1, 30));

            JurnalPembelian::create([
                'no_reff' => "1-{$i}/2026",
                'tanggal' => $tanggal,
                'bukti' => 'INV-2026-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'rp' => rand(1000000, 5000000),
                'keterangan' => "Pembelian barang operasional #{$i}",
                'kelompok_debit_id' => $persediaan->rekening->kelompok_id ?? 1,
                'rekening_debit_id' => $persediaan->rekening_id ?? 1,
                'nomor_bantu_debit_id' => $persediaan->id ?? 1,
                'data_d' => 'D',
                'kelompok_kredit_id' => $hutangUsaha->rekening->kelompok_id ?? 2,
                'rekening_kredit_id' => $hutangUsaha->rekening_id ?? 2,
                'nomor_bantu_kredit_id' => $hutangUsaha->id ?? 2,
                'data_k' => 'K',
                'kode_proyek_id' => $kodeProyek->id ?? null,
                'company_id' => 1,
                'pembelian_items' => [
                    [
                        'id' => 1,
                        'bukti' => 'INV-2026-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                        'keterangan' => "Item pembelian #{$i}",
                        'nomor_bantu_debit_id' => $persediaan->id ?? 1,
                        'jumlah' => rand(1000000, 5000000),
                        'kode_proyek_id' => $kodeProyek->id ?? null,
                    ]
                ],
                'is_confirmed' => $i <= 3, // 3 pertama sudah dikonfirmasi
                'confirmed_by' => $i <= 3 ? 1 : null,
                'confirmed_at' => $i <= 3 ? $tanggal->addHours(2) : null,
                'created_by' => 1,
            ]);
        }
        echo "   ✅ 5 data Jurnal Pembelian Barang berhasil dibuat\n\n";

        // ===== 2. JURNAL REKENING AIR (5 data) =====
        echo "📝 2. Seeding Jurnal Rekening Air...\n";

        for ($i = 1; $i <= 5; $i++) {
            $tanggal = Carbon::now()->subDays(rand(1, 30));
            $totalRp = rand(500000, 3000000);

            $jurnal = JurnalRekeningAir::create([
                'no_reff' => "2-{$i}/2026",
                'tanggal' => $tanggal,
                'bukti' => 'REK-AIR-2026-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'keterangan' => "Tagihan rekening air periode " . $tanggal->format('m/Y'),
                'rekening_air_items' => [
                    [
                        'id' => 1,
                        'bukti' => 'REK-AIR-2026-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                        'keterangan' => "Tagihan pelanggan batch #{$i}",
                        'kelompok_id' => $piutangAir->rekening->kelompok_id ?? 1,
                        'rekening_id' => $piutangAir->rekening_id ?? 1,
                        'nomor_bantu_id' => $piutangAir->id ?? 1,
                        'jumlah' => $totalRp,
                        'kode_proyek_id' => null,
                    ]
                ],
                'rp' => $totalRp,
                'is_confirmed' => $i <= 3,
                'confirmed_at' => $i <= 3 ? $tanggal->addHours(1) : null,
                'company_id' => 1,
                'created_by' => 1,
            ]);

            // Create detail records for UI display
            JurnalRekeningAirDetail::create([
                'jurnal_rekening_air_id' => $jurnal->id,
                'kelompok_id' => $piutangAir->rekening->kelompok_id ?? 1,
                'rekening_id' => $piutangAir->rekening_id ?? 1,
                'nomor_bantu_id' => $piutangAir->id ?? 1,
                'kode_proyek_id' => null,
                'position' => 'debit', // Piutang bertambah di debit
                'jumlah' => $totalRp,
            ]);

            // Kredit: Pendapatan Air
            JurnalRekeningAirDetail::create([
                'jurnal_rekening_air_id' => $jurnal->id,
                'kelompok_id' => $pendapatanAir->rekening->kelompok_id ?? 8,
                'rekening_id' => $pendapatanAir->rekening_id ?? 1,
                'nomor_bantu_id' => $pendapatanAir->id ?? 1,
                'kode_proyek_id' => null,
                'position' => 'kredit', // Pendapatan bertambah di kredit
                'jumlah' => $totalRp,
            ]);
        }
        echo "   ✅ 5 data Jurnal Rekening Air berhasil dibuat\n\n";

        // ===== 3. JURNAL PENERIMAAN KAS (5 data) =====
        echo "📝 3. Seeding Jurnal Penerimaan Kas...\n";

        for ($i = 1; $i <= 5; $i++) {
            $tanggal = Carbon::now()->subDays(rand(1, 30));
            $totalRp = rand(1000000, 5000000);

            $jurnal = JurnalPenerimaanKas::create([
                'tanggal' => $tanggal,
                'nomor_bukti' => 'PKB-2026-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'keterangan' => "Penerimaan pembayaran pelanggan #{$i}",
                'kelompok_id' => $kasBank->rekening->kelompok_id ?? 1,
                'rekening_id' => $kasBank->rekening_id ?? 1,
                'kas_bank_id' => $kasBank->id ?? 1,
                'total_amount' => $totalRp,
                'detail_penerimaan' => [
                    [
                        'kelompok_id' => $pendapatanAir->rekening->kelompok_id ?? 3,
                        'rekening_id' => $pendapatanAir->rekening_id ?? 3,
                        'nomor_bantu_id' => $pendapatanAir->id ?? 3,
                        'keterangan' => "Pembayaran air #{$i}",
                        'jumlah' => $totalRp,
                    ]
                ],
                'reff' => '3',
                'is_confirmed' => $i <= 3,
                'confirmed_by' => $i <= 3 ? 1 : null,
                'confirmed_at' => $i <= 3 ? $tanggal->addHours(1) : null,
                'company_id' => 1,
                'created_by' => 1,
            ]);

            // Create detail
            JurnalPenerimaanKasDetail::create([
                'jurnal_penerimaan_kas_id' => $jurnal->id,
                'kelompok_id' => $pendapatanAir->rekening->kelompok_id ?? 3,
                'rekening_id' => $pendapatanAir->rekening_id ?? 3,
                'nomor_bantu_id' => $pendapatanAir->id ?? 3,
                'nomor_bukti' => 'PKB-2026-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'jumlah' => $totalRp,
                'kode_proyek_id' => null,
            ]);
        }
        echo "   ✅ 5 data Jurnal Penerimaan Kas berhasil dibuat\n\n";

        // ===== 4. JURNAL BAYAR KAS/BANK (5 data) =====
        echo "📝 4. Seeding Jurnal Bayar Kas/Bank...\n";

        for ($i = 1; $i <= 5; $i++) {
            $tanggal = Carbon::now()->subDays(rand(1, 30));

            JurnalBayarKasBank::create([
                'no_reff' => "4-{$i}/2026",
                'no_voucher' => 'VCH-2026-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'tanggal' => $tanggal,
                'tanggal_check' => $tanggal->copy()->addDays(3),
                'bukti' => 'BKK-2026-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'kelompok_id' => $bebanPembelian->rekening->kelompok_id ?? 4,
                'rekening_id' => $bebanPembelian->rekening_id ?? 4,
                'nomor_bantu_id' => $bebanPembelian->id ?? 4,
                'nama_bank' => 'BPD Jawa Tengah',
                'no_cek' => 'CHQ-' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'beban_bagian' => 'Operasional',
                'dibayar_kepada' => "Supplier #{$i}",
                'rp' => rand(500000, 3000000),
                'kode' => 'K',
                'keterangan' => "Pembayaran beban operasional #{$i}",
                'ref' => '4',
                'kode_proyek_id' => $kodeProyek->id ?? null,
                'data' => 'K',
                'company_id' => 1,
                'created_by' => 1,
                'is_confirmed' => $i <= 3,
                'confirmed_by' => $i <= 3 ? 1 : null,
                'confirmed_at' => $i <= 3 ? $tanggal->addHours(1) : null,
            ]);
        }
        echo "   ✅ 5 data Jurnal Bayar Kas/Bank berhasil dibuat\n\n";

        // ===== 5. JURNAL PEMAKAIAN BAHAN (5 data) =====
        echo "📝 5. Seeding Jurnal Pemakaian Bahan (JPBIK)...\n";

        for ($i = 1; $i <= 5; $i++) {
            $tanggal = Carbon::now()->subDays(rand(1, 30));

            JurnalPemakaianBahan::create([
                'no_reff' => "5-{$i}/2026",
                'tanggal' => $tanggal,
                'bukti' => 'JPBIK-2026-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'beban_bagian' => 'Produksi',
                'dibayar' => 'Transfer',
                'no_check' => null,
                'kelompok_debit_id' => $bebanPembelian->rekening->kelompok_id ?? 4,
                'rekening_debit_id' => $bebanPembelian->rekening_id ?? 4,
                'nomor_bantu_debit_id' => $bebanPembelian->id ?? 4,
                'data_debit' => 'D',
                'kelompok_kredit_id' => $persediaan->rekening->kelompok_id ?? 1,
                'rekening_kredit_id' => $persediaan->rekening_id ?? 1,
                'nomor_bantu_kredit_id' => $persediaan->id ?? 1,
                'data_kredit' => 'K',
                'rp' => rand(300000, 1500000),
                'keterangan' => "Pemakaian bahan kimia #{$i}",
                'keterangan_1' => "Detail pemakaian #{$i}",
                'ref' => '5',
                'kode_proyek_id' => $kodeProyek->id ?? null,
                'company_id' => 1,
                'created_by' => 1,
                'is_confirmed' => $i <= 3,
                'confirmed_by' => $i <= 3 ? 1 : null,
                'confirmed_at' => $i <= 3 ? $tanggal->addHours(1) : null,
            ]);
        }
        echo "   ✅ 5 data Jurnal Pemakaian Bahan berhasil dibuat\n\n";

        // ===== 6. JURNAL MEMORIAL (5 data) =====
        echo "📝 6. Seeding Jurnal Memorial...\n";

        for ($i = 1; $i <= 5; $i++) {
            $tanggal = Carbon::now()->subDays(rand(1, 30));

            JurnalMemorial::create([
                'no_reff' => "6-{$i}/2026",
                'tanggal' => $tanggal,
                'bukti' => 'MEM-2026-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'kelompok_id' => $bebanPembelian->rekening->kelompok_id ?? 4,
                'rekening_id' => $bebanPembelian->rekening_id ?? 4,
                'nomor_bantu_id' => $bebanPembelian->id ?? 4,
                'rp' => rand(200000, 1000000),
                'kode' => 'D',
                'keterangan' => "Penyesuaian jurnal #{$i}",
                'ref' => '6',
                'kode_proyek_id' => $kodeProyek->id ?? null,
                'data' => 'D',
                'company_id' => 1,
                'is_confirmed' => $i <= 3,
                'confirmed_by' => $i <= 3 ? 1 : null,
                'confirmed_at' => $i <= 3 ? $tanggal->addHours(1) : null,
            ]);
        }
        echo "   ✅ 5 data Jurnal Memorial berhasil dibuat\n\n";

        echo "✅ Seeding selesai! Total: 30 data jurnal\n";
        echo "📊 Breakdown:\n";
        echo "   - Jurnal Pembelian Barang: 5 data\n";
        echo "   - Jurnal Rekening Air: 5 data\n";
        echo "   - Jurnal Penerimaan Kas: 5 data\n";
        echo "   - Jurnal Bayar Kas/Bank: 5 data\n";
        echo "   - Jurnal Pemakaian Bahan: 5 data\n";
        echo "   - Jurnal Memorial: 5 data\n";
        echo "   - Status: 18 dikonfirmasi, 12 pending\n";
    }
}
