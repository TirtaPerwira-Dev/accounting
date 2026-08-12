<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LhkReportDetail extends Model
{
    protected $table = 'laporan_harian_keuangan_details';

    protected $fillable = [
        'laporan_harian_keuangan_id',
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
    public function lhkReport(): BelongsTo
    {
        return $this->belongsTo(LhkReport::class, 'laporan_harian_keuangan_id');
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
        return $this->belongsTo(KodeProyek::class);
    }
}