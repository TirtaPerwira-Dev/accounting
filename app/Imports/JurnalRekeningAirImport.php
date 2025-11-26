<?php

namespace App\Imports;

use App\Models\JurnalRekeningAir;
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

class JurnalRekeningAirImport implements ToCollection, WithHeadingRow, WithValidation
{
    private $errors = [];
    private $importedCount = 0;

    public function collection(Collection $rows)
    {
        DB::beginTransaction();

        try {
            $groupedRows = $rows->groupBy('bukti');

            foreach ($groupedRows as $bukti => $transactionRows) {
                if (empty($bukti)) {
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

                // Calculate total
                $totalRp = 0;
                $rekeningAirItems = [];

                foreach ($transactionRows as $row) {
                    $currentRowNumber = $rows->search($row) + 2;

                    // Find account by separate fields
                    $account = $this->findAccountBySeparateFields($row['rekening'] ?? null, $row['nomor_bantu'] ?? null);
                    if (!$account) {
                        $this->errors[] = "Baris {$currentRowNumber}: Rekening/Nomor Bantu tidak ditemukan: " . ($row['rekening'] ?? '') . '/' . ($row['nomor_bantu'] ?? '');
                        continue 2; // Skip entire transaction
                    }

                    // Parse amount
                    $jumlah = $this->parseAmount($row['jumlah'] ?? 0);
                    if ($jumlah <= 0) {
                        $this->errors[] = "Baris {$currentRowNumber}: Jumlah harus lebih dari 0";
                        continue 2;
                    }

                    // Determine position (D/K -> debit/kredit)
                    $posisiInput = strtoupper($row['posisi'] ?? 'D');
                    if (!in_array($posisiInput, ['D', 'K'])) {
                        $this->errors[] = "Baris {$currentRowNumber}: Posisi harus D atau K";
                        continue 2;
                    }
                    
                    $position = $posisiInput === 'D' ? 'debit' : 'kredit';

                    // Find kode proyek if specified
                    $kodeProyekId = null;
                    if (!empty($row['kode_proyek'])) {
                        $kodeProyek = KodeProyek::where('kode', $row['kode_proyek'])->first();
                        if (!$kodeProyek) {
                            $this->errors[] = "Baris {$currentRowNumber}: Kode proyek tidak ditemukan: " . $row['kode_proyek'];
                            continue 2;
                        }
                        $kodeProyekId = $kodeProyek->id;
                    }

                    $totalRp += $jumlah;

                    $rekeningAirItems[] = [
                        'rekening' => $account['rekening_id'],
                        'nomor_bantu' => $account['nomor_bantu_id'],
                        'kode_proyek' => $kodeProyekId,
                        'jumlah' => $jumlah,
                        'position' => $position,
                    ];
                }

                // Validate balance
                $totalDebit = collect($rekeningAirItems)->where('position', 'debit')->sum('jumlah');
                $totalKredit = collect($rekeningAirItems)->where('position', 'kredit')->sum('jumlah');

                if (abs($totalDebit - $totalKredit) > 0.01) {
                    $this->errors[] = "Transaksi {$bukti}: Total Debit (Rp " . number_format($totalDebit) . ") tidak sama dengan Total Kredit (Rp " . number_format($totalKredit) . ")";
                    continue;
                }

                // Generate nomor referensi
                $noReff = $this->generateNoReff();

                // Create jurnal rekening air
                JurnalRekeningAir::create([
                    'no_reff' => $noReff,
                    'tanggal' => $tanggal,
                    'bukti' => strtoupper($bukti),
                    'keterangan' => $firstRow['keterangan'] ?? '',
                    'rekening_air_items' => $rekeningAirItems,
                    'rp' => $totalRp,
                    'company_id' => 1,
                    'is_confirmed' => false,
                ]);

                $this->importedCount++;
            }

            if (empty($this->errors)) {
                DB::commit();
                Log::info("Jurnal Rekening Air Import: {$this->importedCount} records imported successfully");
            } else {
                DB::rollBack();
                Log::error("Jurnal Rekening Air Import failed", ['errors' => $this->errors]);
                throw new \Exception('Import failed: ' . implode(', ', $this->errors));
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Jurnal Rekening Air Import Exception', [
                'message' => $e->getMessage(),
                'errors' => $this->errors
            ]);
            throw $e;
        }
    }

    public function rules(): array
    {
        return [
            '*.bukti' => 'required',
            '*.tanggal' => 'required',
            '*.rekening' => 'required',
            '*.nomor_bantu' => 'required',
            '*.jumlah' => 'required|numeric|min:1',
            '*.posisi' => 'required|in:D,K',
            '*.keterangan' => 'required|string|max:500',
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

    private function findAccountBySeparateFields($noRek, $noBantu)
    {
        if (empty($noRek) || empty($noBantu)) return null;

        // Find rekening by no_rek
        $rekening = Rekening::where('no_rek', $noRek)->first();
        if (!$rekening) return null;

        // Find nomor_bantu by no_bantu
        $nomorBantu = NomorBantu::where('rekening_id', $rekening->id)
            ->where('no_bantu', $noBantu)
            ->first();
        if (!$nomorBantu) return null;

        return [
            'kelompok_id' => $rekening->kelompok_id,
            'rekening_id' => $rekening->id,
            'nomor_bantu_id' => $nomorBantu->id,
            'kode' => $nomorBantu->kode,
        ];
    }

    private function parseAmount($amount)
    {
        if (is_numeric($amount)) return (float) $amount;
        
        $cleaned = preg_replace('/[^\d.-]/', '', $amount);
        return (float) $cleaned;
    }

    private function generateNoReff(): string
    {
        $year = date('Y');
        $lastEntry = JurnalRekeningAir::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastEntry ? (int)explode('-', $lastEntry->no_reff)[1] + 1 : 1;
        return "2-{$nextNumber}/{$year}";
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