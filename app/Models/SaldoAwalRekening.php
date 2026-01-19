<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaldoAwalRekening extends Model
{
    use HasFactory;

    protected $table = 'saldo_awal_rekening';

    protected $fillable = [
        'tahun',
        'rekening_id',
        'nomor_bantu_id',
        'saldo_awal',
        'posisi',
        'keterangan',
    ];

    protected $casts = [
        'tahun' => 'integer',
        'saldo_awal' => 'decimal:2',
    ];

    /**
     * Relasi ke Rekening
     */
    public function rekening(): BelongsTo
    {
        return $this->belongsTo(Rekening::class);
    }

    /**
     * Relasi ke Nomor Bantu
     */
    public function nomorBantu(): BelongsTo
    {
        return $this->belongsTo(NomorBantu::class);
    }

    /**
     * Scope untuk filter berdasarkan tahun
     */
    public function scopeTahun($query, $tahun)
    {
        return $query->where('tahun', $tahun);
    }

    /**
     * Scope untuk filter berdasarkan rekening
     */
    public function scopeRekening($query, $rekeningId)
    {
        return $query->where('rekening_id', $rekeningId);
    }

    /**
     * Scope untuk filter berdasarkan posisi (Debit/Kredit)
     */
    public function scopePosisi($query, $posisi)
    {
        return $query->where('posisi', $posisi);
    }

    /**
     * Accessor untuk mendapatkan saldo sesuai posisi
     * Jika Debit = positif, Kredit = negatif (untuk perhitungan)
     */
    public function getSaldoAttribute()
    {
        return $this->posisi === 'D' ? $this->saldo_awal : -$this->saldo_awal;
    }

    /**
     * Accessor untuk nama rekening lengkap dengan kode
     */
    public function getNamaRekeningLengkapAttribute()
    {
        if (!$this->rekening) return '-';

        $nama = "[{$this->rekening->kelompok->no_kel}-{$this->rekening->no_rek}] {$this->rekening->nama_rek}";

        if ($this->nomorBantu) {
            $nama .= " - [{$this->nomorBantu->no_bantu}] {$this->nomorBantu->nm_bantu}";
        }

        return $nama;
    }
}
