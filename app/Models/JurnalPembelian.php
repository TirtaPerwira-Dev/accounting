<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class JurnalPembelian extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'jurnal_pembelians';

    protected $fillable = [
        'no_reff',
        'tanggal',
        'bukti',
        'rp',
        'keterangan',
        'nomor_bantu_kredit_id',
        'nama_nomor_bantu_kredit', // Denormalized for display
        'data_k',
        'data_d',
        'nomor_bantu_debit_id',
        'kode_proyek_id',
        'company_id',
        'is_confirmed',
        'confirmed_by',
        'confirmed_at',
        'created_by',
        'deleted_by',
        'group_transaksi',
        'item_sequence',
        'is_posted',
        'posted_at',
        'posted_by',
        'journal_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'rp' => 'decimal:2',
        'is_confirmed' => 'boolean',
        'confirmed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Auto-generate no_reff if not set
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['no_reff', 'tanggal', 'bukti', 'keterangan', 'rp', 'is_confirmed', 'created_by'])
            ->setDescriptionForEvent(fn(string $eventName) => "Jurnal Pembelian has been {$eventName}")
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // === RELATIONSHIPS ===

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function kodeProyek(): BelongsTo
    {
        return $this->belongsTo(KodeProyek::class);
    }

    // === CUSTOM RELATIONSHIPS ===

    /**
     * Get all items dalam group transaksi yang sama
     */
    public function getGroupItemsAttribute()
    {
        if ($this->group_transaksi) {
            return self::where('group_transaksi', $this->group_transaksi)
                ->orderBy('item_sequence')
                ->get();
        }

        // Jika tidak ada group, return collection dengan record ini saja
        return collect([$this]);
    }

    // === RELATIONSHIPS ===
    public function kelompokKredit(): BelongsTo
    {
        // Derive from nomor_bantu_kredit -> rekening -> kelompok
        return $this->belongsTo(NomorBantu::class, 'nomor_bantu_kredit_id')
            ->with(['rekening.kelompok']);
    }

    public function rekeningKredit(): BelongsTo
    {
        // Derive from nomor_bantu_kredit -> rekening
        return $this->belongsTo(NomorBantu::class, 'nomor_bantu_kredit_id')
            ->with(['rekening']);
    }

    public function nomorBantuKredit(): BelongsTo
    {
        return $this->belongsTo(NomorBantu::class, 'nomor_bantu_kredit_id');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    // Relasi untuk akun debit item
    public function kelompokDebit(): BelongsTo
    {
        // Derive from nomor_bantu_debit -> rekening -> kelompok
        return $this->belongsTo(NomorBantu::class, 'nomor_bantu_debit_id')
            ->with(['rekening.kelompok']);
    }

    public function rekeningDebit(): BelongsTo
    {
        // Derive from nomor_bantu_debit -> rekening
        return $this->belongsTo(NomorBantu::class, 'nomor_bantu_debit_id')
            ->with(['rekening']);
    }

    public function nomorBantuDebit(): BelongsTo
    {
        return $this->belongsTo(NomorBantu::class, 'nomor_bantu_debit_id');
    }

    // Relasi ke detail items (jika menggunakan tabel detail terpisah)
    public function details(): HasMany
    {
        return $this->hasMany(JurnalPembelianDetail::class);
    }

    // === METHODS ===

    /**
     * Generate nomor referensi - hanya angka sequential (1, 2, 3, ...)
     */
    public function generateNoReff(): string
    {
        return '1';
    }

    /**
     * Get kode SAKEP for kredit account
     */
    public function getKodeSakepKreditAttribute(): string
    {
        $nomorBantu = $this->nomorBantuKredit;
        if (!$nomorBantu || !$nomorBantu->rekening || !$nomorBantu->rekening->kelompok) {
            return '-';
        }

        return $nomorBantu->rekening->kelompok->no_kel .
            $nomorBantu->rekening->no_rek .
            str_pad($nomorBantu->no_bantu, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Get nama lengkap akun kredit
     */
    public function getNamaAkunKreditAttribute(): string
    {
        return $this->nama_nomor_bantu_kredit ?: ($this->nomorBantuKredit?->nm_bantu ?? '-');
    }

    /**
     * Get kode SAKEP untuk debit account (item)
     */
    public function getKodeSakepDebitAttribute(): string
    {
        $nomorBantu = $this->nomorBantuDebit;
        if (!$nomorBantu || !$nomorBantu->rekening || !$nomorBantu->rekening->kelompok) {
            return '-';
        }

        return $nomorBantu->rekening->kelompok->no_kel .
            $nomorBantu->rekening->no_rek .
            str_pad($nomorBantu->no_bantu, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Get nama lengkap akun debit (item)
     */
    public function getNamaAkunDebitAttribute(): string
    {
        return $this->nomorBantuDebit?->nm_bantu ?? '-';
    }

    /**
     * Get total pembelian dari semua items dalam group
     */
    public function getTotalPembelianAttribute(): float
    {
        if (!$this->group_transaksi) {
            return $this->rp ?? 0;
        }

        return self::where('group_transaksi', $this->group_transaksi)
            ->sum('rp');
    }

    /**
     * Get summary of pembelian items
     */
    public function getPembelianSummaryAttribute(): string
    {
        if ($this->group_transaksi) {
            $items = self::where('group_transaksi', $this->group_transaksi)
                ->orderBy('item_sequence')
                ->get();

            if ($items->count() === 0) {
                return 'Tidak ada item';
            }

            $firstItem = $items->first()->keterangan ?: 'Item pembelian';

            if ($items->count() === 1) {
                return $firstItem;
            }

            return $firstItem . " (+ " . ($items->count() - 1) . " item lainnya)";
        }

        return $this->keterangan ?: 'Item pembelian';
    }

    /**
     * Konfirmasi jurnal
     */
    public function confirm(): void
    {
        $this->update([
            'is_confirmed' => true,
            'confirmed_by' => auth()->id(),
            'confirmed_at' => now(),
        ]);
    }

    /**
     * Batal konfirmasi jurnal
     */
    public function unconfirm(): void
    {
        $this->update([
            'is_confirmed' => false,
            'confirmed_by' => null,
            'confirmed_at' => null,
        ]);
    }

    /**
     * Generate journal entries for posting to General Ledger
     */
    public function generateJournalEntries(): array
    {
        $entries = [];

        // 1. Entry untuk Hutang (Kredit)
        $rekeningKredit = $this->nomorBantuKredit?->rekening;
        $entries[] = [
            'tanggal' => $this->tanggal,
            'bukti' => $this->bukti,
            'rekening_id' => $rekeningKredit?->id,
            'nomor_bantu_id' => $this->nomor_bantu_kredit_id,
            'debit' => 0,
            'kredit' => $this->rp,
            'keterangan' => $this->keterangan,
            'kode_proyek_id' => $this->kode_proyek_id,
            'no_reff' => $this->no_reff,
        ];

        // 2. Entries untuk setiap detail (Debit)
        foreach ($this->details as $detail) {
            $entries[] = [
                'tanggal' => $this->tanggal,
                'bukti' => $this->bukti,
                'rekening_id' => $detail->rekening_id,
                'nomor_bantu_id' => $detail->nomor_bantu_id,
                'debit' => $detail->debit > 0 ? $detail->debit : $detail->jumlah,
                'kredit' => $detail->credit,
                'keterangan' => $detail->keterangan ?? $this->keterangan,
                'kode_proyek_id' => $detail->kode_proyek_id ?? $this->kode_proyek_id,
                'no_reff' => $this->no_reff,
            ];
        }

        return $entries;
    }

    /**
     * Scope untuk filter berdasarkan tahun
     */
    public function scopeByYear($query, $year)
    {
        return $query->whereYear('tanggal', $year);
    }

    /**
     * Scope untuk filter berdasarkan bulan
     */
    public function scopeByMonth($query, $month, $year = null)
    {
        $query->whereMonth('tanggal', $month);

        if ($year) {
            $query->whereYear('tanggal', $year);
        }

        return $query;
    }

    /**
     * Scope untuk jurnal yang belum dikonfirmasi
     */
    public function scopePending($query)
    {
        return $query->where('is_confirmed', false);
    }

    /**
     * Scope untuk jurnal yang sudah dikonfirmasi
     */
    public function scopeConfirmed($query)
    {
        return $query->where('is_confirmed', true);
    }
}
