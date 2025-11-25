<?php
// app/Models/AccountingStandard.php

namespace App\Models;

use App\Traits\HasCacheManagement;
use App\Traits\HasSecurityScopes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class AccountingStandard extends Model
{
    use HasFactory, HasCacheManagement, HasSecurityScopes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [];

    // Mass assignment protection
    protected $guarded = ['id'];

    // Default eager loading to prevent N+1
    protected $with = [];

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        // Clear cache when model is updated
        static::saved(function ($model) {
            $model->clearCache();
        });

        static::deleted(function ($model) {
            $model->clearCache();
        });
    }

    /**
     * Scope for active accounting standards
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by code
     */
    public function scopeByCode(Builder $query, string $code): Builder
    {
        return $query->where('code', $code);
    }

    /**
     * Get companies using this standard
     */
    public function companies(): HasMany
    {
        return $this->hasMany(Company::class, 'accounting_standard_id')
            ->select(['id', 'name', 'npwp', 'accounting_standard_id', 'is_active']);
    }

    /**
     * Get active companies only
     */
    public function activeCompanies(): HasMany
    {
        return $this->companies()->where('is_active', true);
    }

    /**
     * Get cached active standards
     */
    public static function getActiveStandards()
    {
        return static::rememberStatic('active_accounting_standards', 3600, function () {
            return static::active()
                ->select(['id', 'code', 'name', 'description'])
                ->orderBy('code')
                ->get();
        });
    }

    /**
     * Get standard by code with cache
     */
    public static function getByCode(string $code): ?self
    {
        return static::rememberStatic("accounting_standard_{$code}", 3600, function () use ($code) {
            return static::byCode($code)->active()->first();
        });
    }

    /**
     * Get count of companies using this standard
     */
    public function getCompaniesCountAttribute(): int
    {
        return $this->companies()->count();
    }

    /**
     * Check if standard has companies using it
     */
    public function hasCompanies(): bool
    {
        return $this->companies()->exists();
    }

    /**
     * Route key name for model binding
     */
    public function getRouteKeyName(): string
    {
        return 'code';
    }
}
