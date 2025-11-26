<?php

namespace App\Imports;

use App\Models\JurnalPenerimaanKas;
use App\Models\Kelompok;
use App\Models\Rekening;
use App\Models\NomorBantu;
use App\Models\KodeProyek;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class JurnalPenerimaanKasImport implements ToCollection, WithHeadingRow, WithValidation
{
    private $errors = [];
    private $importedCount = 0;

    public function collection(Collection $rows)
    {
        DB::beginTransaction();

        try {
            $groupedRows = $rows->groupBy('nomor_bukti');

            foreach ($groupedRows as $nomorBukti => $transactionRows) {
                if (empty($nomorBukti)) {
                    $this->errors[] = "Nomor bukti tidak boleh kosong";
                    continue;
                }

                $firstRow = $transactionRows->first();
                $rowNumber = $rows->search($firstRow) + 2;

                // Parse tanggal
                $tanggal = $this->parseDate($firstRow['tanggal'] ?? null);
                if (!$tanggal) {
                    $this->errors[] = "Baris {$rowNumber}: Format tanggal tidak valid";
                    continue;
                }

                // Find kas/bank account by codes
                $kelompokId = $this->findKelompokByCode($firstRow['kelompok_kas_bank'] ?? null);
                $rekeningId = $this->findRekeningByCode($firstRow['rekening_kas_bank'] ?? null, $kelompokId);
                $kasBankId = $this->findNomorBantuByCode($firstRow['nomor_bantu_kas_bank'] ?? null, $rekeningId);

                if (!$kelompokId || !$rekeningId || !$kasBankId) {
                    $this->errors[] = "Baris {$rowNumber}: Kas/Bank tidak ditemukan. Periksa kode Kelompok, Rekening, dan Nomor Bantu";
                    continue;
                }

                // Validate kas/bank is activa lancar (10)
                $kelompokKasBank = Kelompok::find($kelompokId);
                if (!$kelompokKasBank || $kelompokKasBank->no_kel !== '10') {
                    $this->errors[] = "Baris {$rowNumber}: Kas/Bank harus dari kelompok Aktiva Lancar (10)";
                    continue;
                }

                // Process detail penerimaan
                $detailPenerimaan = [];
                $totalAmount = 0;

                foreach ($transactionRows as $row) {
                    $currentRowNumber = $rows->search($row) + 2;

                    // Find source rekening and nomor bantu by codes
                    $sourceRekeningId = $this->findRekeningByCode($row['rekening_sumber'] ?? null);
                    $sourceNomorBantuId = $this->findNomorBantuByCode($row['nomor_bantu_sumber'] ?? null, $sourceRekeningId);
                    
                    if (!$sourceRekeningId || !$sourceNomorBantuId) {
                        $this->errors[] = "Baris {$currentRowNumber}: Kode rekening sumber atau kode nomor bantu sumber tidak ditemukan";
                        continue 2; // Skip entire transaction
                    }

                    // Parse amount
                    $jumlah = $this->parseAmount($row['jumlah'] ?? 0);
                    if ($jumlah <= 0) {
                        $this->errors[] = "Baris {$currentRowNumber}: Jumlah harus lebih dari 0";
                        continue 2;
                    }

                    // Find kode proyek if specified
                    $kodeProyekId = null;
                    if (!empty($row['kode_proyek'])) {
                        $kodeProyek = KodeProyek::where('kode', $row['kode_proyek'])
                            ->orWhere('name', 'like', '%' . $row['kode_proyek'] . '%')
                            ->first();
                        if (!$kodeProyek) {
                            $this->errors[] = "Baris {$currentRowNumber}: Kode proyek tidak ditemukan: " . $row['kode_proyek'];
                            continue 2;
                        }
                        $kodeProyekId = $kodeProyek->id;
                    }

                    $totalAmount += $jumlah;

                    $detailPenerimaan[] = [
                        'nomor_bukti' => strtoupper($row['nomor_bukti_detail'] ?? $nomorBukti),
                        'rekening' => $sourceRekeningId,
                        'nomor_bantu' => $sourceNomorBantuId,
                        'kode_proyek' => $kodeProyekId,
                        'jumlah' => $jumlah,
                        'keterangan_item' => $row['keterangan_detail'] ?? '',
                    ];
                }

                // Create jurnal penerimaan kas
                JurnalPenerimaanKas::create([
                    'kelompok_id' => $kelompokId,
                    'rekening_id' => $rekeningId,
                    'kas_bank_id' => $kasBankId,
                    'tanggal' => $tanggal,
                    'nomor_bukti' => strtoupper($nomorBukti),
                    'keterangan' => $firstRow['keterangan_umum'] ?? '',
                    'detail_penerimaan' => $detailPenerimaan,
                    'total_amount' => $totalAmount,
                    'reff' => $firstRow['reff'] ?? '3',
                ]);

                $this->importedCount++;
            }

            if (empty($this->errors)) {
                DB::commit();
                Log::info("Jurnal Penerimaan Kas Import: {$this->importedCount} records imported successfully");
            } else {
                DB::rollBack();
                Log::error("Jurnal Penerimaan Kas Import failed", ['errors' => $this->errors]);
                throw new \Exception('Import failed: ' . implode(', ', $this->errors));
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Jurnal Penerimaan Kas Import Exception', [
                'message' => $e->getMessage(),
                'errors' => $this->errors
            ]);
            throw $e;
        }
    }

    public function rules(): array
    {
        return [
            '*.nomor_bukti' => 'required',
            '*.tanggal' => 'required',
            '*.kelompok_kas_bank' => 'required',
            '*.rekening_kas_bank' => 'required', 
            '*.nomor_bantu_kas_bank' => 'required',
            '*.rekening_sumber' => 'required',
            '*.nomor_bantu_sumber' => 'required',
            '*.jumlah' => 'required|numeric|min:1',
            '*.keterangan_umum' => 'required|string|max:500',
        ];
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

    private function findKelompokByCode($kodeKelompok)
    {
        if (empty($kodeKelompok)) return null;
        
        $kelompok = Kelompok::where('no_kel', $kodeKelompok)->first();
        return $kelompok ? $kelompok->id : null;
    }
    
    private function findRekeningByCode($kodeRekening, $kelompokId = null)
    {
        if (empty($kodeRekening)) return null;
        
        $query = Rekening::where('no_rek', $kodeRekening);
            
        if ($kelompokId) {
            $query->where('kelompok_id', $kelompokId);
        }
        
        $rekening = $query->first();
        return $rekening ? $rekening->id : null;
    }
    
    private function findNomorBantuByCode($kodeBantu, $rekeningId = null)
    {
        if (empty($kodeBantu)) return null;
        
        $query = NomorBantu::where('no_bantu', $kodeBantu);
            
        if ($rekeningId) {
            $query->where('rekening_id', $rekeningId);
        }
        
        $nomorBantu = $query->first();
        return $nomorBantu ? $nomorBantu->id : null;
    }

    private function parseAmount($amount)
    {
        if (is_numeric($amount)) return (float) $amount;
        
        $cleaned = preg_replace('/[^\d.-]/', '', $amount);
        return (float) $cleaned;
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