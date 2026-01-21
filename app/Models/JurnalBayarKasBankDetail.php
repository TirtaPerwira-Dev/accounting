<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JurnalBayarKasBankDetail extends Model
{
    protected $fillable = [
        'jurnal_bayar_kas_bank_id',
        'no_voucher',
        'keterangan',
        'jumlah',
        'dibayar_kepada',
        'kelompok_id',
        'rekening_id',
        'nomor_bantu_id',
        'kode_proyek_id',
    ];

    protected $casts = [
        'jumlah' => 'decimal:2',
    ];

    // Relations
    public function jurnalBayarKasBank(): BelongsTo
    {
        return $this->belongsTo(JurnalBayarKasBank::class);
    }

    public function kelompok(): BelongsTo
    {
        return $this->belongsTo(Kelompok::class);
    }

    public function rekening(): BelongsTo
    {
        return $this->belongsTo(Rekening::class);
    }

    public function nomorBantu(): BelongsTo
    {
        return $this->belongsTo(NomorBantu::class);
    }

    public function kodeProyek(): BelongsTo
    {
        return $this->belongsTo(KodeProyek::class);
    }
}
