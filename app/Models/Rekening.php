<?php

namespace App\Models;

use App\Traits\HasCacheManagement;
use App\Traits\HasSecurityScopes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rekening extends Model
{
    use HasFactory, HasCacheManagement, HasSecurityScopes;

    protected $fillable = [
        'kelompok_id',
        'no_rek',
        'nama_rek',
        'kode',
        'kel',
        'data',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Kode enum values
    public const KODE_VALUES = [
        'D' => 'Debit',
        'K' => 'Kredit',
    ];

    // KEL enum values (same as Kelompok)
    public const KEL_VALUES = [
        '1' => 'Kategori 1',
        '2' => 'Kategori 2',
        '3' => 'Kategori 3',
        '4' => 'Kategori 4',
        '5' => 'Kategori 5',
        '6' => 'Kategori 6',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $rekening): void {
            if (!empty($rekening->kel) || empty($rekening->kelompok_id)) {
                return;
            }

            $parentKel = Kelompok::query()
                ->whereKey($rekening->kelompok_id)
                ->value('kel');

            if (!empty($parentKel)) {
                $rekening->kel = (string) $parentKel;
            }
        });
    }

    /**
     * Scope for active rekening
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by kelompok
     */
    public function scopeByKelompok(Builder $query, int $kelompokId): Builder
    {
        return $query->where('kelompok_id', $kelompokId);
    }

    /**
     * Scope by kode (D/K)
     */
    public function scopeByKode(Builder $query, string $kode): Builder
    {
        return $query->where('kode', $kode);
    }

    /**
     * Get kelompok that owns this rekening
     */
    public function kelompok(): BelongsTo
    {
        return $this->belongsTo(Kelompok::class, 'kelompok_id');
    }

    /**
     * Get nomor bantus under this rekening
     */
    public function nomorBantus(): HasMany
    {
        return $this->hasMany(NomorBantu::class, 'rekening_id')->orderBy('no_bantu');
    }

    /**
     * Get active nomor bantus only
     */
    public function activeNomorBantus(): HasMany
    {
        return $this->nomorBantus()->where('is_active', true);
    }

    /**
     * Get journal details that use this rekening
     */
    public function journalDetails(): HasMany
    {
        return $this->hasMany(JournalDetail::class, 'rekening_id');
    }

    /**
     * Get opening balances that use this rekening
     */
    public function openingBalances(): HasMany
    {
        return $this->hasMany(OpeningBalance::class, 'rekening_id');
    }

    /**
     * Get full SAKEP code (KEL.REK)
     */
    public function getFullCodeAttribute(): string
    {
        return $this->kelompok->no_kel . '.' . $this->no_rek;
    }

    public function getNomorKelompokAttribute(): ?string
    {
        return $this->kelompok?->no_kel;
    }

    public function getNomorRekeningAttribute(): string
    {
        return $this->no_rek;
    }

    /**
     * Get formatted kode
     */
    public function getFormattedKodeAttribute(): string
    {
        return self::KODE_VALUES[$this->kode] ?? $this->kode;
    }
}
