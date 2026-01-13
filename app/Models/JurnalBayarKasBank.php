<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class JurnalBayarKasBank extends Model
{
    use SoftDeletes, LogsActivity;
    protected $fillable = [
        'no_reff',
        'no_voucher',
        'tanggal',
        'tanggal_check',
        'bukti',
        'kelompok_id',
        'rekening_id',
        'nomor_bantu_id',
        'nama_bank',
        'no_cek',
        'beban_bagian',
        'dibayar_kepada',
        'rp',
        'kode',
        'keterangan',
        'ref',
        'kode_proyek_id',
        'data',
        'group_transaksi',
        'item_sequence',
        'company_id',
        'created_by',
        'is_confirmed',
        'confirmed_by',
        'confirmed_at'
    ];

    protected $casts = [
        'tanggal' => 'date',
        'tanggal_check' => 'date',
        'rp' => 'decimal:2',
        'is_confirmed' => 'boolean',
        'confirmed_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['no_reff', 'tanggal', 'bukti', 'keterangan', 'rp', 'is_confirmed', 'created_by'])
            ->setDescriptionForEvent(fn(string $eventName) => "Jurnal Bayar Kas/Bank has been {$eventName}")
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function kelompok(): BelongsTo
    {
        return $this->belongsTo(Kelompok::class);
    }
    public function rekening(): BelongsTo
    {
        return $this->belongsTo(Rekening::class);
    }
    public function nomorBantu(): BelongsTo
    {
        return $this->belongsTo(NomorBantu::class);
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

    /**
     * Generate nomor referensi - hanya angka sequential (4, 5, 6, ...)
     */
    public function generateNoReff(): string
    {
        $lastJurnal = self::orderBy('id', 'desc')->first();

        if ($lastJurnal && is_numeric($lastJurnal->no_reff)) {
            return (string)((int)$lastJurnal->no_reff + 1);
        }

        return '4'; // Start from 4
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
    }
}
