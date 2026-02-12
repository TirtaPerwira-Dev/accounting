<?php

namespace App\Imports;

use App\Models\JurnalPemakaianBahan;
use App\Models\JurnalPemakaianBahanDetail;
use App\Models\Kelompok;
use App\Models\Rekening;
use App\Models\NomorBantu;
use App\Models\KodeProyek;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class JurnalPemakaianBahanImport implements ToCollection, WithHeadingRow
{
    protected $errors = [];
    protected $importedCount = 0;

    public function collection(Collection $rows)
    {
        DB::beginTransaction();

        try {
            $currentGroupId = null;
            $currentNoReff = null;
            $itemSequence = 1;

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;

                // Skip empty rows
                if (empty($row->filter()->toArray())) {
                    continue;
                }

                if (empty($row['bukti']) || empty($row['tanggal'])) {
                    $this->errors[] = "Baris {$rowNumber}: Bukti dan Tanggal wajib diisi";
                    continue;
                }

                // Check if this is a new group
                $prevRow = $rows[$index - 1] ?? null;
                $isNewGroup = !$currentGroupId ||
                    ($prevRow && ($prevRow['tanggal'] !== $row['tanggal'] || $prevRow['bukti'] !== $row['bukti']));

                if ($isNewGroup) {
                    $currentGroupId = 'GROUP_' . time() . '_' . Str::random(6);
                    $currentNoReff = null;
                    $itemSequence = 1;
                } else {
                    $itemSequence++;
                }

                // Parse tanggal
                $tanggal = $this->parseDate($row['tanggal']);
                if (!$tanggal) {
                    $this->errors[] = "Baris {$rowNumber}: Format tanggal tidak valid";
                    continue;
                }

                // Find kelompok debit
                $kelompokDebit = Kelompok::where('no_kel', $row['kelompok_debit'])->first();
                if (!$kelompokDebit) {
                    $this->errors[] = "Baris {$rowNumber}: Kelompok Debit tidak ditemukan: " . $row['kelompok_debit'];
                    continue;
                }

                // Find rekening debit
                $rekeningDebit = Rekening::where('no_rek', $row['rekening_debit'])
                    ->where('kelompok_id', $kelompokDebit->id)
                    ->first();
                if (!$rekeningDebit) {
                    $this->errors[] = "Baris {$rowNumber}: Rekening Debit tidak ditemukan: " . $row['rekening_debit'];
                    continue;
                }

                // Find nomor bantu debit
                $nomorBantuDebit = NomorBantu::where('no_bantu', $row['nomor_bantu_debit'])
                    ->where('rekening_id', $rekeningDebit->id)
                    ->first();
                if (!$nomorBantuDebit) {
                    $this->errors[] = "Baris {$rowNumber}: Nomor Bantu Debit tidak ditemukan: " . $row['nomor_bantu_debit'];
                    continue;
                }

                // Find kelompok kredit
                $kelompokKredit = Kelompok::where('no_kel', $row['kelompok_kredit'])->first();
                if (!$kelompokKredit) {
                    $this->errors[] = "Baris {$rowNumber}: Kelompok Kredit tidak ditemukan: " . $row['kelompok_kredit'];
                    continue;
                }

                // Find rekening kredit
                $rekeningKredit = Rekening::where('no_rek', $row['rekening_kredit'])
                    ->where('kelompok_id', $kelompokKredit->id)
                    ->first();
                if (!$rekeningKredit) {
                    $this->errors[] = "Baris {$rowNumber}: Rekening Kredit tidak ditemukan: " . $row['rekening_kredit'];
                    continue;
                }

                // Find nomor bantu kredit
                $nomorBantuKredit = NomorBantu::where('no_bantu', $row['nomor_bantu_kredit'])
                    ->where('rekening_id', $rekeningKredit->id)
                    ->first();
                if (!$nomorBantuKredit) {
                    $this->errors[] = "Baris {$rowNumber}: Nomor Bantu Kredit tidak ditemukan: " . $row['nomor_bantu_kredit'];
                    continue;
                }

                // Find kode proyek if exists
                $kodeProyekId = null;
                if (!empty($row['kode_proyek'])) {
                    $kodeProyek = KodeProyek::where('kode', $row['kode_proyek'])->first();
                    if (!$kodeProyek) {
                        $this->errors[] = "Baris {$rowNumber}: Kode Proyek tidak ditemukan: " . $row['kode_proyek'];
                        continue;
                    }
                    $kodeProyekId = $kodeProyek->id;
                }

                // Parse amount
                $jumlah = $this->parseAmount($row['jumlah'] ?? 0);
                if ($jumlah <= 0) {
                    $this->errors[] = "Baris {$rowNumber}: Jumlah harus lebih dari 0";
                    continue;
                }

                // Create or find header record
                $jurnal = null;
                if ($isNewGroup) {
                    $jurnal = JurnalPemakaianBahan::create([
                        'bukti' => strtoupper($row['bukti']),
                        'tanggal' => $tanggal,
                        'keterangan' => $row['keterangan'] ?? null,
                        'beban_bagian' => $row['beban_bagian'] ?? null,
                        'kode_proyek_id' => $kodeProyekId,
                        'no_reff' => '5',
                        'ref' => '5',
                        'group_transaksi' => $currentGroupId,
                        'company_id' => Auth::user()?->company_id ?? 1,
                        'created_by' => Auth::id(),
                        'is_confirmed' => false,
                        'rp' => 0,
                    ]);
                    $currentGroupId = $jurnal->id;
                }

                // Create Detail record
                JurnalPemakaianBahanDetail::create([
                    'jurnal_pemakaian_bahan_id' => $currentGroupId,
                    'bukti' => strtoupper($row['bukti']),
                    'keterangan' => $row['keterangan'] ?? null,
                    'jumlah' => $jumlah,
                    'beban_bagian' => $row['beban_bagian'] ?? null,
                    'kelompok_debit_id' => $kelompokDebit->id,
                    'rekening_debit_id' => $rekeningDebit->id,
                    'nomor_bantu_debit_id' => $nomorBantuDebit->id,
                    'kelompok_kredit_id' => $kelompokKredit->id,
                    'rekening_kredit_id' => $rekeningKredit->id,
                    'nomor_bantu_kredit_id' => $nomorBantuKredit->id,
                    'kode_proyek_id' => $kodeProyekId,
                ]);

                $this->importedCount++;
            }

            if (empty($this->errors)) {
                DB::commit();
            } else {
                DB::rollBack();
                throw new \Exception('Import failed: ' . implode(', ', $this->errors));
            }
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function parseDate($date)
    {
        if (empty($date)) return null;

        try {
            $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'];
            foreach ($formats as $format) {
                $parsed = Carbon::createFromFormat($format, $date);
                if ($parsed && $parsed->format($format) === $date) {
                    return $parsed->toDateString();
                }
            }

            if (is_numeric($date)) {
                return Carbon::createFromFormat('Y-m-d', '1900-01-01')
                    ->addDays($date - 2)
                    ->toDateString();
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function parseAmount($amount)
    {
        if (is_numeric($amount)) return (float) $amount;
        $amount = preg_replace('/[^0-9.,]/', '', $amount);
        $amount = str_replace(['.', ','], ['', '.'], $amount);
        return (float) $amount;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }
}
