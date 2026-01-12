<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JurnalBayarKasBank;
use App\Models\JurnalPemakaianBahan;
use App\Models\JurnalMemorial;
use App\Models\Rekening;
use App\Models\NomorBantu;
use App\Models\KodeProyek;
use Carbon\Carbon;

class JurnalTestSeeder extends Seeder
{
    public function run(): void
    {
        echo "🧪 Testing Manual Input Jurnal...\n\n";

        // Get sample data
        $rekening = Rekening::with('kelompok')->first();
        $nomorBantu = NomorBantu::first();
        $kodeProyek = KodeProyek::first();
        $rekeningBank = Rekening::where('no_rek', 'like', '1102%')->first();

        if (!$rekening || !$nomorBantu) {
            echo "❌ Error: Tidak ada data rekening atau nomor bantu\n";
            return;
        }

        // === TEST 1: JURNAL BAYAR KAS/BANK ===
        echo "1️⃣  Testing Jurnal Bayar Kas/Bank (7 records dengan group_transaksi)...\n";
        try {
            // Get multiple rekening for details
            $allRekening = Rekening::with('nomorBantus')->limit(7)->get();
            
            $noVoucher = 'BKB-' . date('Ymd') . '-' . rand(100, 999);
            $groupTransaksi = \Illuminate\Support\Str::uuid()->toString();
            $keteranganTemplates = ['Bayar Lembur', 'Perbaikan Kebocoran', 'Pembelian Bahan', 'Biaya Operasional', 'Gaji Karyawan'];
            
            $totalCreated = 0;
            foreach ($allRekening as $index => $rek) {
                $jumlah = rand(500000, 2000000);
                $template = $keteranganTemplates[$index % 5];
                
                JurnalBayarKasBank::create([
                    'no_voucher' => $noVoucher,
                    'tanggal_check' => Carbon::now(),
                    'tanggal' => Carbon::now(),
                    'no_reff' => '3',
                    'rekening_id' => $rek->id,
                    'kelompok_id' => $rek->kelompok_id,
                    'nomor_bantu_id' => $rek->nomorBantus->first()?->id ?? $nomorBantu->id,
                    'nama_bank' => $rekeningBank?->nama_rek ?? 'Bank Test',
                    'no_cek' => 'CEK-' . rand(1000, 9999),
                    'beban_bagian' => 'Operasional',
                    'dibayar_kepada' => 'PT Test Vendor',
                    'rp' => $jumlah,
                    'kode' => 'K',
                    'keterangan' => $template . ' - ' . now()->format('d/m/Y'),
                    'ref' => '3',
                    'company_id' => 1,
                    'created_by' => 1,
                    'is_confirmed' => false,
                    'group_transaksi' => $groupTransaksi,
                    'item_sequence' => $index + 1,
                ]);
                $totalCreated++;
            }
            
            echo "   ✅ Jurnal Bayar Kas/Bank created - {$totalCreated} records\n";
            echo "   📋 No Voucher: {$noVoucher}\n";
            echo "   🔗 Group Transaksi: {$groupTransaksi}\n";
            echo "   📦 Detail Items: {$totalCreated} items\n\n";
        } catch (\Exception $e) {
            echo "   ❌ Error: " . $e->getMessage() . "\n\n";
        }

        // === TEST 2: JPBIK (Jurnal Pemakaian Bahan) ===
        echo "2️⃣  Testing JPBIK (6 records dengan group_transaksi)...\n";
        try {
            $allRekening2 = Rekening::with(['kelompok', 'nomorBantus'])->skip(10)->limit(6)->get();
            
            $bukti = 'JPBIK-' . rand(100, 999);
            $groupTransaksi = \Illuminate\Support\Str::uuid()->toString();
            $keteranganList = ['Pemakaian Kaporit', 'Pemakaian PAC', 'Pemakaian Tawas', 'BBM Solar', 'Listrik PLN', 'Bahan Pembantu'];
            
            $totalCreated = 0;
            foreach ($allRekening2 as $index => $rek) {
                $debit = ($index % 2 == 0) ? rand(300000, 800000) : 0;
                $kredit = ($index % 2 == 1) ? rand(300000, 800000) : 0;
                
                JurnalPemakaianBahan::create([
                    'tanggal' => Carbon::now(),
                    'bukti' => $bukti,
                    'no_reff' => '4',
                    'beban_bagian' => 'Gudang',
                    'dibayar' => 'PT Supplier ABC',
                    'no_check' => null,
                    'rp' => $debit > 0 ? $debit : $kredit,
                    'rekening_debit_id' => $rek->id,
                    'kelompok_debit_id' => $rek->kelompok_id,
                    'nomor_bantu_debit_id' => $rek->nomorBantus->first()?->id ?? $nomorBantu->id,
                    'rekening_kredit_id' => $rek->id,
                    'kelompok_kredit_id' => $rek->kelompok_id,
                    'nomor_bantu_kredit_id' => $rek->nomorBantus->first()?->id ?? $nomorBantu->id,
                    'keterangan' => $keteranganList[$index],
                    'keterangan_1' => 'Detail 1',
                    'keterangan_2' => 'Detail 2',
                    'kode_proyek_id' => $kodeProyek?->id,
                    'ref' => '4',
                    'company_id' => 1,
                    'created_by' => 1,
                    'is_confirmed' => false,
                    'group_transaksi' => $groupTransaksi,
                    'item_sequence' => $index + 1,
                ]);
                $totalCreated++;
            }
            
            echo "   ✅ JPBIK created - {$totalCreated} records\n";
            echo "   📋 No Bukti: {$bukti}\n";
            echo "   🔗 Group Transaksi: {$groupTransaksi}\n";
            echo "   📦 Beban Bagian: Gudang\n";
            echo "   📦 Detail Items: {$totalCreated} items\n\n";
        } catch (\Exception $e) {
            echo "   ❌ Error: " . $e->getMessage() . "\n\n";
        }

        // === TEST 3: JURNAL MEMORIAL ===
        echo "3️⃣  Testing Jurnal Memorial (8 records dengan group_transaksi)...\n";
        try {
            $allRekening3 = Rekening::with(['kelompok', 'nomorBantus'])->skip(20)->limit(8)->get();
            
            $bukti = 'MEM-' . rand(100, 999);
            $groupTransaksi = \Illuminate\Support\Str::uuid()->toString();
            $keteranganMem = [
                'Penyesuaian Depresiasi',
                'Amortisasi Beban Ditangguhkan',
                'Penyisihan Piutang',
                'Beban Akrual',
                'Pendapatan Diterima Dimuka',
                'Biaya Dibayar Dimuka',
                'Koreksi Pembukuan',
                'Reklasifikasi Akun'
            ];
            
            $totalCreated = 0;
            foreach ($allRekening3 as $index => $rek) {
                $debit = ($index < 4) ? rand(400000, 900000) : 0;
                $kredit = ($index >= 4) ? rand(400000, 900000) : 0;
                $kode = $debit > 0 ? 'D' : 'K';
                
                JurnalMemorial::create([
                    'tanggal' => Carbon::now(),
                    'bukti' => $bukti,
                    'no_reff' => '6',
                    'rekening_id' => $rek->id,
                    'kelompok_id' => $rek->kelompok_id,
                    'nomor_bantu_id' => $rek->nomorBantus->first()?->id ?? $nomorBantu->id,
                    'rp' => $debit > 0 ? $debit : $kredit,
                    'kode' => $kode,
                    'keterangan' => $keteranganMem[$index],
                    'kode_proyek_id' => $kodeProyek?->id,
                    'ref' => '6',
                    'company_id' => 1,
                    'created_by' => 1,
                    'is_confirmed' => false,
                    'group_transaksi' => $groupTransaksi,
                    'item_sequence' => $index + 1,
                ]);
                $totalCreated++;
            }
            
            echo "   ✅ Jurnal Memorial created - {$totalCreated} records\n";
            echo "   📋 No Bukti: {$bukti}\n";
            echo "   🔗 Group Transaksi: {$groupTransaksi}\n";
            echo "   � Detail Items: {$totalCreated} items (4 Debit + 4 Kredit)\n\n";
        } catch (\Exception $e) {
            echo "   ❌ Error: " . $e->getMessage() . "\n\n";
        }

        echo "✅ Selesai testing manual input!\n";
        echo "📊 Summary:\n";
        echo "   - Jurnal Bayar Kas/Bank: 7 separate records with group_transaksi\n";
        echo "   - JPBIK: 6 separate records with group_transaksi\n";
        echo "   - Jurnal Memorial: 8 separate records with group_transaksi\n";
        echo "\n💡 Cek data di:\n";
        echo "   - Jurnal Bayar Kas/Bank: /accounting/jurnal-bayar-kas-bank\n";
        echo "   - JPBIK: /accounting/jurnal-pemakaian-bahan\n";
        echo "   - Jurnal Memorial: /accounting/jurnal-memorial\n";
    }
}
