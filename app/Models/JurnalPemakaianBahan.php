<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class JurnalPemakaianBahan extends Model
{
    use SoftDeletes, LogsActivity;
    protected $fillable = [
        'no_reff',
        'tanggal',
        'bukti',
        'beban_bagian',
        'dibayar',
        'no_check',
        'kelompok_debit_id',
        'rekening_debit_id',
        'nomor_bantu_debit_id',
        'data_debit',
        'kelompok_kredit_id',
        'rekening_kredit_id',
        'nomor_bantu_kredit_id',
        'data_kredit',
        'rp',
        'keterangan',
        'keterangan_1',
        'keterangan_2',
        'keterangan_3',
        'keterangan_4',
        'ref',
        'kode_proyek_id',
        'group_transaksi',
        'item_sequence',
        'company_id',
        'created_by',
        'deleted_by',
        'is_confirmed',
        'confirmed_by',
        'confirmed_at'
    ];

    protected $casts = [
        'tanggal' => 'date',
        'rp' => 'decimal:2',
        'is_confirmed' => 'boolean',
        'confirmed_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['no_reff', 'tanggal', 'bukti', 'keterangan', 'rp', 'is_confirmed', 'created_by'])
            ->setDescriptionForEvent(fn(string $eventName) => "Jurnal Pemakaian Bahan has been {$eventName}")
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function kelompokDebit(): BelongsTo
    {
        return $this->belongsTo(Kelompok::class, 'kelompok_debit_id');
    }
    public function rekeningDebit(): BelongsTo
    {
        return $this->belongsTo(Rekening::class, 'rekening_debit_id');
    }
    public function nomorBantuDebit(): BelongsTo
    {
        return $this->belongsTo(NomorBantu::class, 'nomor_bantu_debit_id');
    }
    public function kelompokKredit(): BelongsTo
    {
        return $this->belongsTo(Kelompok::class, 'kelompok_kredit_id');
    }
    public function rekeningKredit(): BelongsTo
    {
        return $this->belongsTo(Rekening::class, 'rekening_kredit_id');
    }
    public function nomorBantuKredit(): BelongsTo
    {
        return $this->belongsTo(NomorBantu::class, 'nomor_bantu_kredit_id');
    }
    public function kodeProyek(): BelongsTo
    {
        return $this->belongsTo(KodeProyek::class);
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

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function details(): HasMany
    {
        return $this->hasMany(JurnalPemakaianBahanDetail::class);
    }

    /**
     * Generate nomor referensi - hanya angka sequential (5, 6, 7, ...)
     */
    public function generateNoReff(): string
    {
        return '5';
    }

    /**
     * Boot model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Auto-generate no_reff if not set
            if (empty($model->no_reff)) {
                $model->no_reff = $model->generateNoReff();
            }

            if (empty($model->company_id)) {
                $model->company_id = 1;
            }
            if (empty($model->created_by) && auth()->check()) {
                $model->created_by = auth()->id();
            }
        });

        static::deleting(function ($model) {
            if (auth()->check()) {
                $model->deleted_by = auth()->id();
                $model->saveQuietly();
            }
        });
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Konfirmasi jurnal (approval)
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
