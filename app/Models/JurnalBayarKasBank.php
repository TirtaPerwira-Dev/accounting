<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'item_sequence',
        'company_id',
        'created_by',
        'deleted_by',
        'is_confirmed',
        'confirmed_by',
        'confirmed_at',
        'is_posted',
        'posted_at',
        'posted_by',
        'journal_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'tanggal_check' => 'date',
        'rp' => 'decimal:2',
        'is_confirmed' => 'boolean',
        'confirmed_at' => 'datetime',
        'is_posted' => 'boolean',
        'posted_at' => 'datetime',
    ];

    /**
     * Accessor untuk menampilkan nama bank
     * Jika ada nomor_bantu_id, ambil dari nama bantu
     * Jika tidak, ambil dari rekening nama_rek
     */
    public function getNamaBankDisplayAttribute(): string
    {
        // Prioritas 1: Jika field nama_bank sudah diisi langsung
        if (!empty($this->nama_bank)) {
            return $this->nama_bank;
        }

        // Prioritas 2: Ambil dari nomor bantu jika ada
        if ($this->nomorBantu) {
            return $this->nomorBantu->nm_bantu;
        }

        // Prioritas 3: Ambil dari rekening
        if ($this->rekening) {
            return $this->rekening->nama_rek;
        }

        return '-';
    }

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

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function details(): HasMany
    {
        return $this->hasMany(JurnalBayarKasBankDetail::class);
    }

    /**
     * Generate nomor referensi - tetap '4' untuk Jurnal Bayar Kas/Bank
     */
    public function generateNoReff(): string
    {
        return '4';
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

    /**
     * Generate journal entries for posting to General Ledger
     */
    public function generateJournalEntries(): array
    {
        $entries = [];

        // 1. Entry untuk Kas/Bank (Kredit) - Total RP
        $entries[] = [
            'tanggal' => $this->tanggal,
            'bukti' => $this->bukti,
            'rekening_id' => $this->rekening_id,
            'nomor_bantu_id' => $this->nomor_bantu_id,
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
                'debit' => $detail->debit, // Biaya/Hutang biasanya di debit jika itu pembayaran
                'kredit' => $detail->credit,
                'keterangan' => $detail->keterangan ?? $this->keterangan,
                'kode_proyek_id' => $this->kode_proyek_id,
                'no_reff' => $this->no_reff,
            ];
        }

        return $entries;
    }
}
