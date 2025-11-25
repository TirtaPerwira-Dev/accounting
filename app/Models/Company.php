<?php
// app/Models/Company.php

namespace App\Models;

use App\Traits\HasCacheManagement;
use App\Traits\HasSecurityScopes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class Company extends Model
{
    use HasFactory, HasCacheManagement, HasSecurityScopes;

    protected $fillable = [
        'name',
        'npwp',
        'address',
        'phone',
        'logo',
        'accounting_standard_id',
        'config',
        'is_active'
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [];

    // Mass assignment protection
    protected $guarded = ['id'];

    // Default eager loading to prevent N+1
    protected $with = [];

    // Default config structure
    protected $attributes = [
        'config' => '{"ppn_rate": 11, "currency": "IDR", "fiscal_year_start": "01-01"}'
    ];

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
     * Scope for active companies
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by NPWP
     */
    public function scopeByNpwp(Builder $query, string $npwp): Builder
    {
        return $query->where('npwp', $npwp);
    }

    /**
     * Scope by accounting standard
     */
    public function scopeByStandard(Builder $query, int $standardId): Builder
    {
        return $query->where('accounting_standard_id', $standardId);
    }

    /**
     * Get accounting standard with optimized query
     */
    public function standard(): BelongsTo
    {
        return $this->belongsTo(AccountingStandard::class, 'accounting_standard_id')
            ->select(['id', 'code', 'name', 'is_active']);
    }

    /**
     * Get SAKEP journals for this company
     */
    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class, 'company_id');
    }

    /**
     * Get SAKEP accounts hierarchy (from accounting standard)
     */
    public function getSakepAccountsHierarchy(): array
    {
        return $this->remember("company_{$this->id}_sakep_hierarchy", 1800, function () {
            if (!$this->standard) {
                return [];
            }

            // Get all active nomor bantu with their relationships
            $accounts = NomorBantu::with(['rekening.kelompok'])
                ->whereHas('rekening.kelompok', function ($query) {
                    $query->where('standard_id', $this->accounting_standard_id);
                })
                ->where('is_active', true)
                ->get();

            return $this->buildSakepTree($accounts);
        });
    }

    /**
     * Build SAKEP tree structure (Kelompok -> Rekening -> NomorBantu)
     */
    private function buildSakepTree($accounts): array
    {
        $tree = [];

        foreach ($accounts as $account) {
            $kelompokKey = $account->rekening->kelompok->no_kel;
            $rekeningKey = $account->rekening->no_rek;

            // Initialize kelompok if not exists
            if (!isset($tree[$kelompokKey])) {
                $tree[$kelompokKey] = [
                    'id' => $account->rekening->kelompok->id,
                    'code' => $account->rekening->kelompok->no_kel,
                    'name' => $account->rekening->kelompok->nama_kel,
                    'type' => 'kelompok',
                    'children' => []
                ];
            }

            // Initialize rekening if not exists
            if (!isset($tree[$kelompokKey]['children'][$rekeningKey])) {
                $tree[$kelompokKey]['children'][$rekeningKey] = [
                    'id' => $account->rekening->id,
                    'code' => $account->rekening->no_rek,
                    'name' => $account->rekening->nama_rek,
                    'type' => 'rekening',
                    'children' => []
                ];
            }

            // Add nomor bantu
            $tree[$kelompokKey]['children'][$rekeningKey]['children'][] = [
                'id' => $account->id,
                'code' => $account->full_code,
                'name' => $account->nm_bantu,
                'type' => 'nomor_bantu'
            ];
        }

        return array_values($tree);
    }

    /**
     * Initialize SAKEP structure for this company
     */
    public function initializeSakepStructure(): bool
    {
        // SAKEP is already initialized via seeders
        // This method can be used for future SAKEP customizations
        return true;
    }

    /**
     * Get config value with default
     */
    public function getConfigValue(string $key, $default = null)
    {
        $config = $this->config ?? [];
        return data_get($config, $key, $default);
    }

    /**
     * Set config value
     */
    public function setConfigValue(string $key, $value): void
    {
        $config = $this->config ?? [];
        data_set($config, $key, $value);
        $this->config = $config;
        $this->save();
    }

    /**
     * Get PPN rate from config
     */
    public function getPpnRateAttribute(): float
    {
        return (float) $this->getConfigValue('ppn_rate', 11);
    }

    /**
     * Get currency from config
     */
    public function getCurrencyAttribute(): string
    {
        return $this->getConfigValue('currency', 'IDR');
    }

    /**
     * Get fiscal year start from config
     */
    public function getFiscalYearStartAttribute(): string
    {
        return $this->getConfigValue('fiscal_year_start', '01-01');
    }

    /**
     * Get logo URL
     */
    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? asset('storage/' . $this->logo) : null;
    }

    /**
     * Get masked NPWP for display
     */
    public function getMaskedNpwpAttribute(): string
    {
        if (strlen($this->npwp) >= 10) {
            return substr($this->npwp, 0, 4) . str_repeat('*', strlen($this->npwp) - 8) . substr($this->npwp, -4);
        }
        return $this->npwp;
    }

    /**
     * Get journal entries count for this company
     */
    public function getJournalsCountAttribute(): int
    {
        return $this->journals()->count();
    }

    /**
     * Get active journal entries count for this company
     */
    public function getActiveJournalsCountAttribute(): int
    {
        return $this->journals()->whereNotNull('date')->count();
    }

    /**
     * Check if company has journal entries
     */
    public function hasJournals(): bool
    {
        return $this->journals()->exists();
    }

    /**
     * Check if SAKEP is available
     */
    public function isSakepInitialized(): bool
    {
        return $this->accounting_standard_id !== null;
    }

    /**
     * Get company by NPWP with cache
     */
    public static function getByNpwp(string $npwp): ?self
    {
        return static::rememberStatic("company_npwp_{$npwp}", 1800, function () use ($npwp) {
            return static::byNpwp($npwp)->active()->first();
        });
    }

    /**
     * Route key name for model binding
     */
    public function getRouteKeyName(): string
    {
        return 'npwp';
    }
}
