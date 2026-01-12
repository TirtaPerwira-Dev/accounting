<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SaldoAwalJurnal extends Model
{
    use HasFactory;

    protected $table = 'saldo_awal_jurnal';

    protected $fillable = [
        'jenis_jurnal',
        'tahun',
        'saldo_debit',
        'saldo_kredit',
        'keterangan',
    ];

    protected $casts = [
        'tahun' => 'integer',
        'saldo_debit' => 'decimal:2',
        'saldo_kredit' => 'decimal:2',
    ];

    /**
     * Scope untuk filter berdasarkan tahun
     */
    public function scopeTahun($query, $tahun)
    {
        return $query->where('tahun', $tahun);
    }

    /**
     * Scope untuk filter berdasarkan jenis jurnal
     */
    public function scopeJenisJurnal($query, $jenis)
    {
        return $query->where('jenis_jurnal', $jenis);
    }

    /**
     * Accessor untuk mendapatkan selisih
     */
    public function getSelisihAttribute()
    {
        return $this->saldo_debit - $this->saldo_kredit;
    }

    /**
     * Accessor untuk nama jenis jurnal
     */
    public function getNamaJenisJurnalAttribute()
    {
        return match ($this->jenis_jurnal) {
            'rekening_air' => 'Jurnal Rekening Air',
            'pemakaian_bahan' => 'Jurnal Pemakaian Bahan (JPBIK)',
            'memorial' => 'Jurnal Memorial',
            'pembelian' => 'Jurnal Pembelian',
            'bayar_kas_bank' => 'Jurnal Bayar Kas Bank',
            'penerimaan_kas' => 'Jurnal Penerimaan Kas',
            default => $this->jenis_jurnal,
        };
    }
}
