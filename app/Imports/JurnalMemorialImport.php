<?php

namespace App\Imports;

use App\Models\JurnalMemorial;
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

class JurnalMemorialImport implements ToCollection, WithHeadingRow
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

                // Find kelompok by no_kel
                $kelompok = Kelompok::where('no_kel', $row['kelompok'])->first();
                if (!$kelompok) {
                    $this->errors[] = "Baris {$rowNumber}: Kelompok tidak ditemukan: " . $row['kelompok'];
                    continue;
                }

                // Find rekening by no_rek
                $rekening = Rekening::where('no_rek', $row['rekening'])
                    ->where('kelompok_id', $kelompok->id)
                    ->first();
                if (!$rekening) {
                    $this->errors[] = "Baris {$rowNumber}: Rekening tidak ditemukan: " . $row['rekening'];
                    continue;
                }

                // Find nomor bantu
                $nomorBantu = NomorBantu::where('no_bantu', $row['nomor_bantu'])
                    ->where('rekening_id', $rekening->id)
                    ->first();
                if (!$nomorBantu) {
                    $this->errors[] = "Baris {$rowNumber}: Nomor Bantu tidak ditemukan: " . $row['nomor_bantu'];
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

                // Validate kode (D/K)
                $kode = strtoupper($row['kode'] ?? 'D');
                if (!in_array($kode, ['D', 'K'])) {
                    $this->errors[] = "Baris {$rowNumber}: Kode harus D atau K";
                    continue;
                }

                // Create record
                $jurnal = JurnalMemorial::create([
                    'bukti' => strtoupper($row['bukti']),
                    'tanggal' => $tanggal,
                    'kelompok_id' => $kelompok->id,
                    'rekening_id' => $rekening->id,
                    'nomor_bantu_id' => $nomorBantu->id,
                    'kode' => $kode,
                    'rp' => $jumlah,
                    'keterangan' => $row['keterangan'] ?? null,
                    'kode_proyek_id' => $kodeProyekId,
                    'data' => $rekening->data,
                    'ref' => '6',
                    'group_transaksi' => $currentGroupId,
                    'item_sequence' => $itemSequence,
                    'company_id' => Auth::user()?->company_id ?? 1,
                    'created_by' => Auth::id(),
                    'is_confirmed' => false,
                ]);

                // Save no_reff for group
                if ($isNewGroup) {
                    $currentNoReff = $jurnal->no_reff;
                }

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
