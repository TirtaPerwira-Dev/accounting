<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JurnalMemorialDetail extends Model
{
    protected $fillable = [
        'jurnal_memorial_id',
        'bukti',
        'keterangan',
        'jumlah',
        'posisi',
        'kelompok_id',
        'rekening_id',
        'nomor_bantu_id',
        'kode_proyek_id',
    ];

    protected $casts = [
        'jumlah' => 'decimal:2',
    ];

    // Relations
    public function jurnalMemorial(): BelongsTo
    {
        return $this->belongsTo(JurnalMemorial::class);
    }

    /**
     * Backward-compatibility accessor for code paths that expect a details() relation.
     */
    public function details(): HasMany
    {
        return $this->hasMany(self::class, 'jurnal_memorial_id', 'jurnal_memorial_id');
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
