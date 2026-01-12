<?php

namespace App\Imports;

use App\Models\JurnalPemakaianBahan;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class JurnalPemakaianBahanImport implements ToCollection, WithHeadingRow
{
    protected $errors = [];
    protected $importedCount = 0;

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            try {
                if (empty($row['tanggal']) || empty($row['bukti'])) {
                    $this->errors[] = "Baris " . ($index + 2) . ": Tanggal dan No. Bukti wajib diisi";
                    continue;
                }

                JurnalPemakaianBahan::create([
                    'tanggal' => \Carbon\Carbon::parse($row['tanggal']),
                    'bukti' => $row['bukti'],
                    'no_reff' => $row['no_reff'] ?? 'JPBIK-' . date('YmdHis'),
                    'beban_bagian' => $row['beban_bagian'] ?? null,
                    'dibayar' => $row['dibayar'] ?? null,
                    'no_check' => $row['no_check'] ?? null,
                    'rp' => $row['rp'] ?? 0,
                    'keterangan' => $row['keterangan'] ?? null,
                    'ref' => '4',
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
