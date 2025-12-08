<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JurnalRekeningAirDetail extends Model
{
    protected $table = 'jurnal_rekening_air_details';

    protected $fillable = [
        'jurnal_rekening_air_id',
        'kelompok_id',
        'rekening_id',
        'nomor_bantu_id',
        'kode_proyek_id',
        'position', // 'debit' atau 'kredit'
        'jumlah',
    ];

    protected $casts = [
        'jumlah' => 'decimal:2',
    ];

    // === RELATIONSHIPS ===

    public function jurnalRekeningAir(): BelongsTo
    {
        return $this->belongsTo(JurnalRekeningAir::class);
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
