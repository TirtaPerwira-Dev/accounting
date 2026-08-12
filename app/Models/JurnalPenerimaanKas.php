<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
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
        'lampiran',
        'total_item_input',
        'nominal_input',
        'detail_penerimaan',
        'total_amount',
        'no_reff',
        'created_by',
        'deleted_by',
        'is_confirmed',
        'confirmed_by',
        'confirmed_at',
        'company_id',
        'is_posted',
        'posted_at',
        'posted_by',
        'journal_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'total_amount' => 'decimal:2',
        'nominal_input' => 'decimal:2',
        'total_item_input' => 'integer',
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
            if (empty($model->no_reff)) {
                $model->no_reff = $model->generateNoReff();
            }
            if (empty($model->created_by) && Auth::check()) {
                $model->created_by = Auth::id();
            }
            if (empty($model->company_id)) {
                $model->company_id = 1;
            }
        });

        static::deleting(function ($model) {
            if (Auth::check()) {
                $model->deleted_by = Auth::id();
                $model->saveQuietly();
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

    public function details(): HasMany
    {
        return $this->hasMany(JurnalPenerimaanKasDetail::class, 'jurnal_penerimaan_kas_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
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

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Generate nomor referensi - tetap '3' untuk Jurnal Penerimaan Kas
     */
    public function generateNoReff(): string
    {
        return '3';
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
        return $this->tanggal ? $this->tanggal->format('d/m/Y') : '';
    }

    public function getFormattedTotalRpAttribute(): string
    {
        return 'Rp ' . number_format((float) ($this->rp ?? 0), 0, ',', '.');
    }

    /**
     * Get total dari semua items dalam repeater
     * Alias: total_from_items dan total_kredit akan mengembalikan nilai yang sama
     */
    public function getTotalFromItemsAttribute(): float
    {
        return (float) $this->details()->sum('jumlah');
    }

    /**
     * Alias untuk getTotalFromItemsAttribute - untuk backward compatibility
     */
    public function getTotalKreditAttribute(): float
    {
        return $this->total_from_items;
    }

    public function generateJournalEntries(): array
    {
        $entries = [];

        if (!$this->relationLoaded('details')) {
            $this->load('details');
        }

        if ($this->details->isEmpty()) {
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
                'no_reff' => $this->no_reff,
            ];
        }

        // Entry untuk setiap sumber penerimaan (Kredit)
        foreach ($this->details as $item) {
            $entries[] = [
                'tanggal' => $this->tanggal,
                'bukti' => $this->nomor_bukti,
                'rekening_id' => $item->rekening_id,
                'nomor_bantu_id' => $item->nomor_bantu_id,
                'debit' => 0,
                'kredit' => $item->jumlah, // Sumber penerimaan (kredit)
                'keterangan' => $item->keterangan_item ?? $this->keterangan,
                'kode_proyek_id' => $item->kode_proyek_id,
                'no_reff' => $this->no_reff,
            ];
        }

        return $entries;
    }

    /**
     * Konfirmasi jurnal (approval)
     */
    public function confirm(): void
    {
        $this->update([
            'is_confirmed' => true,
            'confirmed_by' => Auth::id(),
            'confirmed_at' => now(),
        ]);
    }

    /**
     * Batalkan konfirmasi jurnal
     */
    public function unconfirm(): void
    {
        $this->update([
            'is_confirmed' => false,
            'confirmed_by' => null,
            'confirmed_at' => null,
        ]);
    }
}
