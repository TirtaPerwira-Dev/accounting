<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JurnalPemakaianBahanDetail extends Model
{
    protected $fillable = [
        'jurnal_pemakaian_bahan_id',
        'bukti',
        'keterangan',
        'jumlah',
        'beban_bagian',
        'kelompok_debit_id',
        'rekening_debit_id',
        'nomor_bantu_debit_id',
        'kelompok_kredit_id',
        'rekening_kredit_id',
        'nomor_bantu_kredit_id',
        'kode_proyek_id',
    ];

    protected $casts = [
        'jumlah' => 'decimal:2',
    ];

    // Relations
    public function jurnalPemakaianBahan(): BelongsTo
    {
        return $this->belongsTo(JurnalPemakaianBahan::class);
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

    public function kelompokKredit(): BelongsTo
    {
        return $this->belongsTo(Kelompok::class, 'kelompok_kredit_id');
    }

    public function rekeningKredit(): BelongsTo
    {
        return $this->belongsTo(Rekening::class, 'rekening_kredit_id');
    }

    public function nomorBantuKredit(): BelongsTo
    {
        return $this->belongsTo(NomorBantu::class, 'nomor_bantu_kredit_id');
    }

    public function kodeProyek(): BelongsTo
    {
        return $this->belongsTo(KodeProyek::class);
    }
}
