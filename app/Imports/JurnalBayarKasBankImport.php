<?php

namespace App\Imports;

use App\Models\JurnalBayarKasBank;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class JurnalBayarKasBankImport implements ToCollection, WithHeadingRow
{
    protected $errors = [];
    protected $importedCount = 0;

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            try {
                if (empty($row['no_voucher']) || empty($row['tanggal_check'])) {
                    $this->errors[] = "Baris " . ($index + 2) . ": No Voucher dan Tanggal Check wajib diisi";
                    continue;
                }

                JurnalBayarKasBank::create([
                    'no_voucher' => $row['no_voucher'],
                    'tanggal_check' => \Carbon\Carbon::parse($row['tanggal_check']),
                    'tanggal' => \Carbon\Carbon::parse($row['tanggal_check']),
                    'no_reff' => $row['no_reff'] ?? 'BKB-' . date('YmdHis'),
                    'nama_bank' => $row['nama_bank'] ?? null,
                    'no_cek' => $row['no_cek'] ?? null,
                    'beban_bagian' => $row['beban_bagian'] ?? null,
                    'dibayar_kepada' => $row['dibayar_kepada'] ?? null,
                    'rp' => $row['rp'] ?? 0,
                    'keterangan' => $row['keterangan'] ?? null,
                    'ref' => '3',
                    'company_id' => 1,
                    'created_by' => Auth::id(),
                    'is_confirmed' => false,
                ]);

                $this->importedCount++;
            } catch (\Exception $e) {
                $this->errors[] = "Baris " . ($index + 2) . ": " . $e->getMessage();
            }
        }
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
