<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JurnalPembelianDetail extends Model
{
    protected $table = 'jurnal_pembelian_details';

    protected $fillable = [
        'jurnal_pembelian_id',
        'bukti',
        'keterangan',
        'jumlah',
        'kelompok_debit_id',
        'rekening_debit_id',
        'nomor_bantu_debit_id',
        'kode_proyek_id',
    ];

    protected $casts = [
        'jumlah' => 'decimal:2',
    ];

    // === RELATIONSHIPS ===

    public function jurnalPembelian(): BelongsTo
    {
        return $this->belongsTo(JurnalPembelian::class);
    }

    public function kelompokDebit(): BelongsTo
    {
        return $this->belongsTo(Kelompok::class, 'kelompok_debit_id');
    }

    public function rekeningDebit(): BelongsTo
    {
        return $this->belongsTo(Rekening::class, 'rekening_debit_id');
    }

    public function nomorBantuDebit(): BelongsTo
    {
        return $this->belongsTo(NomorBantu::class, 'nomor_bantu_debit_id');
    }

    public function kodeProyek(): BelongsTo
    {
        return $this->belongsTo(KodeProyek::class);
    }

    // === ACCESSORS ===

    /**
     * Get kode SAKEP lengkap untuk akun debit
     */
    public function getKodeSakepDebitAttribute(): string
    {
        if (!$this->kelompokDebit || !$this->rekeningDebit || !$this->nomorBantuDebit) {
            return '-';
        }

        return $this->kelompokDebit->no_kel .
            $this->rekeningDebit->no_rek .
            str_pad($this->nomorBantuDebit->no_bantu, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Get nama lengkap akun debit
     */
    public function getNamaAkunDebitAttribute(): string
    {
        return $this->nomorBantuDebit?->nm_bantu ?? '-';
    }
}
