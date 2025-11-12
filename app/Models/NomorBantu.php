<?php

namespace App\Models;

use App\Traits\HasCacheManagement;
use App\Traits\HasSecurityScopes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class NomorBantu extends Model
{
    use HasFactory, HasCacheManagement, HasSecurityScopes;

    protected $fillable = [
        'rekening_id',
        'no_bantu',
        'nm_bantu',
        'kel',
        'kode',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // KEL enum values
    public const KEL_VALUES = [
        '1' => 'Kategori 1',
        '2' => 'Kategori 2',
        '3' => 'Kategori 3',
        '4' => 'Kategori 4',
        '5' => 'Kategori 5',
        '6' => 'Kategori 6',
    ];

    // Kode enum values
    public const KODE_VALUES = [
        'D' => 'Debit',
        'K' => 'Kredit',
    ];

    /**
     * Scope for active nomor bantu
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by rekening
     */
    public function scopeByRekening(Builder $query, int $rekeningId): Builder
    {
        return $query->where('rekening_id', $rekeningId);
    }

    /**
     * Scope by kel category
     */
    public function scopeByKel(Builder $query, string $kel): Builder
    {
        return $query->where('kel', $kel);
    }

    /**
     * Scope by kode (D/K)
     */
    public function scopeByKode(Builder $query, string $kode): Builder
    {
        return $query->where('kode', $kode);
    }

    /**
     * Get rekening that owns this nomor bantu
     */
    public function rekening(): BelongsTo
    {
        return $this->belongsTo(Rekening::class, 'rekening_id');
    }

    /**
     * Get kelompok through rekening (indirect relationship)
     */
    public function kelompok(): HasOneThrough
    {
        return $this->hasOneThrough(
            Kelompok::class,    // Target model
            Rekening::class,    // Intermediate model
            'id',              // Foreign key on intermediate model (rekening.id)
            'id',              // Foreign key on target model (kelompok.id)
            'rekening_id',     // Local key on current model (nomor_bantu.rekening_id)
            'kelompok_id'      // Local key on intermediate model (rekening.kelompok_id)
        );
    }

    /**
     * Alternative: Get kelompok through hasOneThrough
     */
    public function kelompokThrough()
    {
        return $this->hasOneThrough(
            Kelompok::class,
            Rekening::class,
            'id', // Foreign key on Rekening table
            'id', // Foreign key on Kelompok table
            'rekening_id', // Local key on NomorBantu table
            'kelompok_id' // Local key on Rekening table
        );
    }

    /**
     * Get journal details that use this nomor bantu
     */
    public function journalDetails(): HasMany
    {
        return $this->hasMany(JournalDetail::class, 'nomor_bantu_id');
    }

    /**
     * Get opening balances that use this nomor bantu
     */
    public function openingBalances(): HasMany
    {
        return $this->hasMany(OpeningBalance::class, 'nomor_bantu_id');
    }

    /**
     * Get full SAKEP code (KEL.REK.BANTU)
     */
    public function getFullCodeAttribute(): string
    {
        return $this->rekening->kelompok->no_kel . '.' . $this->rekening->no_rek . '.' . $this->no_bantu;
    }

    /**
     * Get formatted KEL
     */
    public function getFormattedKelAttribute(): string
    {
        return self::KEL_VALUES[$this->kel] ?? $this->kel;
    }

    /**
     * Get formatted kode
     */
    public function getFormattedKodeAttribute(): string
    {
        return self::KODE_VALUES[$this->kode] ?? $this->kode;
    }
}
