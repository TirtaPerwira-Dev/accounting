<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class JurnalRekeningAir extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'jurnal_rekening_air';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'no_reff',
        'tanggal',
        'bukti',
        'keterangan',
        'lampiran',
        'total_item_input',
        'total_item_input_debit',
        'total_item_input_kredit',
        'nominal_input',
        'nominal_input_debit',
        'nominal_input_kredit',
        'rekening_air_items',
        'rp',
        'is_confirmed',
        'confirmed_by',
        'confirmed_at',
        'company_id',
        'created_by',
        'deleted_by',
        'is_posted',
        'posted_at',
        'posted_by',
        'journal_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'rp' => 'decimal:2',
        'nominal_input' => 'decimal:2',
        'nominal_input_debit' => 'decimal:2',
        'nominal_input_kredit' => 'decimal:2',
        'total_item_input' => 'integer',
        'total_item_input_debit' => 'integer',
        'total_item_input_kredit' => 'integer',
        'rekening_air_items' => 'array', // Cast JSON to array
        'is_confirmed' => 'boolean',
        'confirmed_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['no_reff', 'tanggal', 'bukti', 'keterangan', 'rp', 'is_confirmed'])
            ->setDescriptionForEvent(fn(string $eventName) => "Jurnal Rekening Air has been {$eventName}")
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->no_reff)) {
                $model->no_reff = $model->generateNoReff();
            }
            if (empty($model->company_id)) {
                $model->company_id = 1; // Default company
            }
            if (empty($model->created_by) && auth()->check()) {
                $model->created_by = auth()->id();
            }
        });

        static::deleting(function ($model) {
            if (auth()->check()) {
                $model->deleted_by = auth()->id();
                $model->saveQuietly(); // Save without triggering events
            }
        });
    }

    /**
     * Generate nomor referensi - hanya angka sequential (2, 3, 4, ...)
     */
    public function generateNoReff(): string
    {
        return '2';
    }

    // Relations - Hanya yang diperlukan untuk struktur baru

    /**
     * Company relationship
     */
    public function company(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Details relationship (untuk metode add item)
     */
    public function details(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(JurnalRekeningAirDetail::class);
    }

    public function createdBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function journal(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function postedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function deletedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
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
    public function getFormattedNoReffAttribute(): string
    {
        return $this->no_reff;
    }

    public function getFormattedTanggalAttribute(): string
    {
        return $this->tanggal ? $this->tanggal->format('d/m/Y') : '';
    }

    public function getFormattedRpAttribute(): string
    {
        return 'Rp ' . number_format((float) ($this->rp ?? 0), 0, ',', '.');
    }

    /**
     * Get total dari semua items dalam details relationship
     */
    public function getTotalFromItemsAttribute(): float
    {
        return (float) $this->details()->sum('jumlah');
    }

    /**
     * Konfirmasi jurnal
     */
    public function confirm()
    {
        $this->update([
            'is_confirmed' => true,
            'confirmed_by' => auth()->id(),
            'confirmed_at' => now(),
        ]);
    }

    /**
     * Batalkan konfirmasi jurnal
     */
    public function unconfirm()
    {
        $this->update([
            'is_confirmed' => false,
            'confirmed_by' => null,
            'confirmed_at' => null,
        ]);
    }

    /**
     * Check if can be edited
     */
    public function canBeEdited(): bool
    {
        return !$this->is_confirmed;
    }

    /**
     * Generate journal entries untuk integrasi dengan sistem jurnal umum
     */
    public function generateJournalEntries(): array
    {
        $entries = [];

        // Entry untuk setiap item berdasarkan position (debit/kredit)
        foreach ($this->details as $detail) {
            $isDebit = strtolower($detail->position) === 'debit';

            $entries[] = [
                'tanggal' => $this->tanggal,
                'bukti' => $this->bukti,
                'rekening_id' => $detail->rekening_id,
                'nomor_bantu_id' => $detail->nomor_bantu_id,
                'debit' => $isDebit ? $detail->jumlah : 0,
                'kredit' => !$isDebit ? $detail->jumlah : 0,
                'keterangan' => $this->keterangan,
                'kode_proyek_id' => $detail->kode_proyek_id,
                'no_reff' => $this->no_reff,
            ];
        }

        return $entries;
    }
}
