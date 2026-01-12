<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JurnalMemorial extends Model
{
    protected $fillable = [
        'no_reff',
        'tanggal',
        'bukti',
        'kelompok_id',
        'rekening_id',
        'nomor_bantu_id',
        'rp',
        'kode',
        'keterangan',
        'ref',
        'kode_proyek_id',
        'data',
        'group_transaksi',
        'item_sequence',
        'company_id',
        'is_confirmed',
        'confirmed_by',
        'confirmed_at'
    ];

    protected $casts = [
        'tanggal' => 'date',
        'rp' => 'decimal:2',
        'is_confirmed' => 'boolean',
        'confirmed_at' => 'datetime',
    ];

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
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
