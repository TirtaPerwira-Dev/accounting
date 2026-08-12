<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class LhkReport extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'laporan_harian_keuangans';

    protected $fillable = [
        'tanggal',
        'no_reff',
        'nomor_bukti',
        'keterangan',
        'jenis',
        'kas_bank_id',
        'kelompok_id',
        'rekening_id',
        'kode_proyek_id',
        'company_id',
        'is_confirmed',
        'confirmed_by',
        'confirmed_at',
        'created_by',
        'deleted_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'is_confirmed' => 'boolean',
        'confirmed_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['tanggal', 'nomor_bukti', 'keterangan', 'jenis', 'is_confirmed'])
            ->setDescriptionForEvent(fn(string $eventName) => "Laporan Harian Keuangan has been {$eventName}")
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

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function kodeProyek(): BelongsTo
    {
        return $this->belongsTo(KodeProyek::class);
    }

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

    public function details(): HasMany
    {
        return $this->hasMany(LhkReportDetail::class, 'laporan_harian_keuangan_id');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Generate nomor referensi - tetap 'LHK' untuk Laporan Harian Keuangan
     */
    public function generateNoReff(): string
    {
        return 'LHK';
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

    public function scopePemasukan($query)
    {
        return $query->where('jenis', 'pemasukan');
    }

    public function scopePengeluaran($query)
    {
        return $query->where('jenis', 'pengeluaran');
    }

    // Accessors
    public function getFormattedTanggalAttribute(): string
    {
        return $this->tanggal ? $this->tanggal->format('d/m/Y') : '';
    }

    public function getJenisLabelAttribute(): string
    {
        return $this->jenis === 'pemasukan' ? 'Pemasukan' : 'Pengeluaran';
    }

    public function getJenisBadgeColorAttribute(): string
    {
        return $this->jenis === 'pemasukan' ? 'success' : 'danger';
    }

    public function getTotalFromItemsAttribute(): float
    {
        return (float) $this->details()->sum('jumlah');
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

        $totalAmount = $this->total_from_items;
        if ($totalAmount <= 0) {
            return $entries;
        }

        if ($this->jenis === 'pemasukan') {
            // Kas/Bank bertambah (Debit)
            $entries[] = [
                'tanggal' => $this->tanggal,
                'bukti' => $this->nomor_bukti,
                'rekening_id' => $this->kasBank?->rekening_id ?? $this->rekening_id,
                'nomor_bantu_id' => $this->kas_bank_id,
                'debit' => $totalAmount,
                'kredit' => 0,
                'keterangan' => $this->keterangan,
                'kode_proyek_id' => $this->kode_proyek_id,
                'no_reff' => $this->no_reff,
            ];

            // Sumber penerimaan (Kredit) - dari details
            foreach ($this->details as $item) {
                $entries[] = [
                    'tanggal' => $this->tanggal,
                    'bukti' => $item->nomor_bukti ?? $this->nomor_bukti,
                    'rekening_id' => $item->rekening_id,
                    'nomor_bantu_id' => $item->nomor_bantu_id,
                    'debit' => 0,
                    'kredit' => $item->jumlah,
                    'keterangan' => $item->keterangan_item ?? $this->keterangan,
                    'kode_proyek_id' => $item->kode_proyek_id ?? $this->kode_proyek_id,
                    'no_reff' => $this->no_reff,
                ];
            }
        } else {
            // Pengeluaran: Kas/Bank berkurang (Kredit)
            $entries[] = [
                'tanggal' => $this->tanggal,
                'bukti' => $this->nomor_bukti,
                'rekening_id' => $this->kasBank?->rekening_id ?? $this->rekening_id,
                'nomor_bantu_id' => $this->kas_bank_id,
                'debit' => 0,
                'kredit' => $totalAmount,
                'keterangan' => $this->keterangan,
                'kode_proyek_id' => $this->kode_proyek_id,
                'no_reff' => $this->no_reff,
            ];

            // Tujuan pengeluaran (Debit) - dari details
            foreach ($this->details as $item) {
                $entries[] = [
                    'tanggal' => $this->tanggal,
                    'bukti' => $item->nomor_bukti ?? $this->nomor_bukti,
                    'rekening_id' => $item->rekening_id,
                    'nomor_bantu_id' => $item->nomor_bantu_id,
                    'debit' => $item->jumlah,
                    'kredit' => 0,
                    'keterangan' => $item->keterangan_item ?? $this->keterangan,
                    'kode_proyek_id' => $item->kode_proyek_id ?? $this->kode_proyek_id,
                    'no_reff' => $this->no_reff,
                ];
            }
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