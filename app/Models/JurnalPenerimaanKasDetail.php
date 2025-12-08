<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JurnalPenerimaanKasDetail extends Model
{
    protected $table = 'jurnal_penerimaan_kas_details';

    protected $fillable = [
        'jurnal_penerimaan_kas_id',
        'kelompok_id',
        'rekening_id',
        'nomor_bantu_id',
        'kode_proyek_id',
        'nomor_bukti',
        'jumlah',
        'keterangan_item',
    ];

    protected $casts = [
        'jumlah' => 'decimal:2',
    ];

    // Relationships
    public function jurnalPenerimaanKas(): BelongsTo
    {
        return $this->belongsTo(JurnalPenerimaanKas::class, 'jurnal_penerimaan_kas_id');
    }

    public function kelompok(): BelongsTo
    {
        return $this->belongsTo(Kelompok::class, 'kelompok_id');
    }

    public function rekening(): BelongsTo
    {
        return $this->belongsTo(Rekening::class, 'rekening_id');
    }

    public function nomorBantu(): BelongsTo
    {
        return $this->belongsTo(NomorBantu::class, 'nomor_bantu_id');
    }

    public function kodeProyek(): BelongsTo
    {
        return $this->belongsTo(KodeProyek::class, 'kode_proyek_id');
    }
}
