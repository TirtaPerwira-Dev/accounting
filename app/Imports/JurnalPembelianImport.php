<?php

namespace App\Imports;

use App\Models\JurnalPembelian;
use App\Models\Kelompok;
use App\Models\Rekening;
use App\Models\NomorBantu;
use App\Models\KodeProyek;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class JurnalPembelianImport implements ToCollection, WithHeadingRow, WithValidation
{
    private $errors = [];
    private $importedCount = 0;

    public function collection(Collection $rows)
    {
        DB::beginTransaction();

        try {
            $currentGroupId = null;
            $currentNoReff = null;
            $itemSequence = 1;

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // +2 karena header dan 0-based index

                // Skip empty rows
                if (empty($row->filter()->toArray())) {
                    continue;
                }

                // Check if this is a new group (different tanggal or bukti)
                $prevRow = $rows[$index - 1] ?? null;
                $isNewGroup = !$currentGroupId || 
                    ($prevRow && ($prevRow['tanggal'] !== $row['tanggal'] || $prevRow['bukti'] !== $row['bukti']));

                if ($isNewGroup) {
                    $currentGroupId = 'GROUP_' . time() . '_' . Str::random(6);
                    $currentNoReff = $this->generateNoReff();
                    $itemSequence = 1;
                } else {
                    $itemSequence++;
                }

                // Parse tanggal
                $tanggal = $this->parseDate($row['tanggal'] ?? null);
                if (!$tanggal) {
                    $this->errors[] = "Baris {$rowNumber}: Format tanggal tidak valid";
                    continue;
                }

                // Cari rekening kredit
                $rekeningKredit = \App\Models\Rekening::find($row['rekening_kredit'] ?? null);
                if (!$rekeningKredit) {
                    $this->errors[] = "Baris {$rowNumber}: Rekening kredit tidak ditemukan dengan ID: " . ($row['rekening_kredit'] ?? '');
                    continue;
                }

                // Cari nomor bantu kredit
                $nomorBantuKredit = NomorBantu::find($row['nomor_bantu_kredit'] ?? null);
                if (!$nomorBantuKredit || $nomorBantuKredit->rekening_id !== $rekeningKredit->id) {
                    $this->errors[] = "Baris {$rowNumber}: Nomor bantu kredit tidak ditemukan atau tidak sesuai dengan rekening: " . ($row['nomor_bantu_kredit'] ?? '');
                    continue;
                }

                // Cari nomor bantu debit
                $nomorBantuDebit = NomorBantu::with('rekening.kelompok')->find($row['nomor_bantu_debit'] ?? null);
                if (!$nomorBantuDebit) {
                    $this->errors[] = "Baris {$rowNumber}: Nomor bantu debit tidak ditemukan dengan ID: " . ($row['nomor_bantu_debit'] ?? '');
                    continue;
                }

                // Cari kode proyek jika ada
                $kodeProyekId = null;
                if (!empty($row['kode_proyek'])) {
                    $kodeProyek = KodeProyek::find($row['kode_proyek']);
                    if (!$kodeProyek) {
                        $this->errors[] = "Baris {$rowNumber}: Kode proyek tidak ditemukan dengan ID: " . $row['kode_proyek'];
                        continue;
                    }
                    $kodeProyekId = $kodeProyek->id;
                }

                // Parse jumlah
                $jumlah = $this->parseAmount($row['jumlah'] ?? 0);
                if ($jumlah <= 0) {
                    $this->errors[] = "Baris {$rowNumber}: Jumlah harus lebih dari 0";
                    continue;
                }

                // Create jurnal pembelian record
                $data = [
                    'no_reff' => $currentNoReff,
                    'tanggal' => $tanggal,
                    'bukti' => strtoupper($row['bukti'] ?? ''),
                    'rp' => $jumlah,
                    'keterangan' => $row['keterangan'] ?? '',
                    
                    // Akun Kredit (Hutang/Kas/Bank)
                    'kelompok_kredit_id' => $rekeningKredit->kelompok_id,
                    'rekening_kredit_id' => $rekeningKredit->id,
                    'nomor_bantu_kredit_id' => $nomorBantuKredit->id,
                    'nama_nomor_bantu_kredit' => $row['nama_nomor_bantu_kredit'] ?? $nomorBantuKredit->nm_bantu,
                    'data_k' => $rekeningKredit->data,
                    
                    // Akun Debit (Pembelian)
                    'kelompok_debit_id' => $nomorBantuDebit->rekening->kelompok_id,
                    'rekening_debit_id' => $nomorBantuDebit->rekening_id,
                    'nomor_bantu_debit_id' => $nomorBantuDebit->id,
                    'data_d' => $nomorBantuDebit->rekening->data,
                    
                    // Item details
                    'bukti_item' => strtoupper($row['bukti'] ?? ''),
                    'keterangan_item' => $row['keterangan'] ?? '',
                    'jumlah_item' => $jumlah,
                    
                    // Group management
                    'group_transaksi' => $currentGroupId,
                    'item_sequence' => $itemSequence,
                    
                    'kode_proyek_id' => $kodeProyekId,
                    'company_id' => 1,
                    'is_confirmed' => false,
                ];

                JurnalPembelian::create($data);
                $this->importedCount++;
            }

            if (empty($this->errors)) {
                DB::commit();
                Log::info("Jurnal Pembelian Import: {$this->importedCount} records imported successfully");
            } else {
                DB::rollBack();
                Log::error("Jurnal Pembelian Import failed", ['errors' => $this->errors]);
                throw new \Exception('Import failed: ' . implode(', ', $this->errors));
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Jurnal Pembelian Import Exception', [
                'message' => $e->getMessage(),
                'errors' => $this->errors
            ]);
            throw $e;
        }
    }

    public function rules(): array
    {
        return [
            '*.tanggal' => 'required',
            '*.rekening_kredit' => 'required',
            '*.nomor_bantu_kredit' => 'required',
            '*.nomor_bantu_debit' => 'required',
            '*.jumlah' => 'required|numeric|min:1',
            '*.bukti' => 'required|string|max:255',
            '*.keterangan' => 'required|string|max:500',
        ];
    }

    private function parseDate($date)
    {
        if (empty($date)) return null;

        try {
            // Try different date formats
            $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'];
            
            foreach ($formats as $format) {
                $parsed = Carbon::createFromFormat($format, $date);
                if ($parsed && $parsed->format($format) === $date) {
                    return $parsed->toDateString();
                }
            }

            // If it's a number (Excel date serial)
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
        
        // Remove currency symbols and separators
        $cleaned = preg_replace('/[^\d.-]/', '', $amount);
        return (float) $cleaned;
    }

    private function generateNoReff(): string
    {
        $year = date('Y');
        $lastJurnal = JurnalPembelian::where('no_reff', 'LIKE', "1-_%/{$year}")
            ->orderBy('created_at', 'desc')
            ->first();

        $nextNumber = 1;
        if ($lastJurnal && preg_match('/^1-(\d+)\/\d{4}$/', $lastJurnal->no_reff, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        }

        return "1-{$nextNumber}/{$year}";
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