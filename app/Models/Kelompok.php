<?php

namespace App\Models;

use App\Traits\HasCacheManagement;
use App\Traits\HasSecurityScopes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kelompok extends Model
{
    use HasFactory, HasCacheManagement, HasSecurityScopes;

    protected $fillable = [
        'standard_id',
        'no_kel',
        'nama_kel',
        'kel',
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

    /**
     * Scope for active kelompok
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by standard
     */
    public function scopeByStandard(Builder $query, int $standardId): Builder
    {
        return $query->where('standard_id', $standardId);
    }

    /**
     * Scope by kel category
     */
    public function scopeByKel(Builder $query, string $kel): Builder
    {
        return $query->where('kel', $kel);
    }

    /**
     * Get accounting standard
     */
    public function standard(): BelongsTo
    {
        return $this->belongsTo(AccountingStandard::class, 'standard_id');
    }

    /**
     * Get rekenings under this kelompok
     */
    public function rekenings(): HasMany
    {
        return $this->hasMany(Rekening::class, 'kelompok_id')->orderBy('no_rek');
    }

    /**
     * Get active rekenings only
     */
    public function activeRekenings(): HasMany
    {
        return $this->rekenings()->where('is_active', true);
    }

    /**
     * Get journal details that use this kelompok
     */
    public function journalDetails(): HasMany
    {
        return $this->hasMany(JournalDetail::class, 'kelompok_id');
    }

    /**
     * Get opening balances that use this kelompok
     */
    public function openingBalances(): HasMany
    {
        return $this->hasMany(OpeningBalance::class, 'kelompok_id');
    }

    /**
     * Get formatted KEL
     */
    public function getFormattedKelAttribute(): string
    {
        return self::KEL_VALUES[$this->kel] ?? $this->kel;
    }
}
