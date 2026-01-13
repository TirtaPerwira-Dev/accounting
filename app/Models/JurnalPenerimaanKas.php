<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class JurnalPenerimaanKas extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'jurnal_penerimaan_kas';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'kelompok_id',
        'rekening_id',
        'kas_bank_id',
        'tanggal',
        'nomor_bukti',
        'keterangan',
        'detail_penerimaan',
        'total_amount',
        'reff',
        'created_by',
        'is_confirmed',
        'confirmed_by',
        'confirmed_at',
        'company_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'total_amount' => 'decimal:2',
        'detail_penerimaan' => 'array',
        'is_confirmed' => 'boolean',
        'confirmed_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['tanggal', 'nomor_bukti', 'keterangan', 'total_amount', 'is_confirmed'])
            ->setDescriptionForEvent(fn(string $eventName) => "Jurnal Penerimaan Kas has been {$eventName}")
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->tanggal)) {
                $model->tanggal = now()->toDateString();
            }
            if (empty($model->reff)) {
                $model->reff = $model->generateReff();
            }
            if (empty($model->created_by) && auth()->check()) {
                $model->created_by = auth()->id();
            }
            if (empty($model->company_id)) {
                $model->company_id = 1;
            }
        });
    }

    // Relations
    public function kelompok(): BelongsTo
    {
        return $this->belongsTo(Kelompok::class, 'kelompok_id');
    }

    public function rekening(): BelongsTo
    {
        return $this->belongsTo(Rekening::class, 'rekening_id');
    }

    public function kasBank(): BelongsTo
    {
        return $this->belongsTo(NomorBantu::class, 'kas_bank_id');
    }

    public function kodeProyek(): BelongsTo
    {
        return $this->belongsTo(KodeProyek::class);
    }

    public function nomorRekening(): BelongsTo
    {
        return $this->belongsTo(Rekening::class, 'nomor_rekening_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(JurnalPenerimaanKasDetail::class, 'jurnal_penerimaan_kas_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /**
     * Generate nomor referensi - hanya angka sequential (3, 4, 5, ...)
     */
    public function generateReff(): string
    {
        $lastJurnal = self::orderBy('id', 'desc')->first();

        if ($lastJurnal && is_numeric($lastJurnal->reff)) {
            return (string)((int)$lastJurnal->reff + 1);
        }

        return '3'; // Start from 3
    }

    // Scopes
    public function scopeThisYear($query)
    {
        return $query->whereYear('tanggal', date('Y'));
    }

    public function scopeThisMonth($query)
    {
        return $query->whereYear('tanggal', date('Y'))
            ->whereMonth('tanggal', date('m'));
    }

    // Accessors
    public function getFormattedTanggalAttribute(): string
    {
        return $this->tanggal?->format('d/m/Y') ?? '';
    }

    public function getFormattedTotalAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->total_amount, 0, ',', '.');
    }

    /**
     * Get total dari semua items dalam repeater
     */
    public function getTotalFromItemsAttribute(): float
    {
        if (!$this->detail_penerimaan) {
            return 0;
        }

        return collect($this->detail_penerimaan)->sum(function ($item) {
            return floatval($item['jumlah'] ?? 0);
        });
    }

    /**
     * Get total kredit from detail_penerimaan
     */
    public function getTotalKreditAttribute(): float
    {
        if (!$this->detail_penerimaan) {
            return 0;
        }

        return collect($this->detail_penerimaan)->sum(function ($item) {
            return floatval($item['jumlah'] ?? 0);
        });
    }

    /**
     * Generate journal entries untuk integrasi dengan sistem jurnal umum
     */
    public function generateJournalEntries(): array
    {
        $entries = [];

        if (!$this->detail_penerimaan) {
            return $entries;
        }

        // Entry untuk Kas/Bank (Debit)
        $totalKredit = $this->total_kredit;
        if ($totalKredit > 0) {
            $entries[] = [
                'tanggal' => $this->tanggal,
                'bukti' => $this->nomor_bukti,
                'rekening_id' => $this->kasBank?->rekening_id ?? null,
                'nomor_bantu_id' => $this->kas_bank_id,
                'debit' => $totalKredit, // Kas/Bank bertambah (debit)
                'kredit' => 0,
                'keterangan' => $this->keterangan,
                'kode_proyek_id' => null,
                'reff' => $this->reff,
            ];
        }

        // Entry untuk setiap sumber penerimaan (Kredit)
        foreach ($this->detail_penerimaan as $item) {
            $jumlah = floatval($item['jumlah'] ?? 0);

            $entries[] = [
                'tanggal' => $this->tanggal,
                'bukti' => $this->nomor_bukti,
                'rekening_id' => $item['rekening'] ?? null,
                'nomor_bantu_id' => $item['nomor_bantu'] ?? null,
                'debit' => 0,
                'kredit' => $jumlah, // Sumber penerimaan (kredit)
                'keterangan' => $item['keterangan_item'] ?? $this->keterangan,
                'kode_proyek_id' => $item['kode_proyek'] ?? null,
                'reff' => $this->reff,
            ];
        }

        return $entries;
    }
}
