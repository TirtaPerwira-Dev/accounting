<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class JurnalRekeningAir extends Model
{
    protected $table = 'jurnal_rekening_air';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'no_reff',
        'tanggal',
        'bukti',
        'keterangan',
        'rekening_air_items',
        'rp',
        'is_confirmed',
        'confirmed_at',
        'company_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'rp' => 'decimal:2',
        'rekening_air_items' => 'array', // Cast JSON to array
        'is_confirmed' => 'boolean',
        'confirmed_at' => 'datetime',
    ];

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
        });
    }

    /**
     * Generate nomor referensi otomatis
     * Format: 2-{urutan}/2024
     */
    public function generateNoReff(): string
    {
        $year = date('Y');
        $lastEntry = static::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastEntry ? (int)explode('-', $lastEntry->no_reff)[1] + 1 : 1;

        return "2-{$nextNumber}/{$year}";
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
        return $this->tanggal?->format('d/m/Y') ?? '';
    }

    public function getFormattedRpAttribute(): string
    {
        return 'Rp ' . number_format($this->rp, 0, ',', '.');
    }

    /**
     * Get total dari semua items dalam repeater
     */
    public function getTotalFromItemsAttribute(): float
    {
        if (!$this->rekening_air_items) {
            return 0;
        }

        return collect($this->rekening_air_items)->sum(function ($item) {
            return floatval($item['jumlah'] ?? 0);
        });
    }

    /**
     * Konfirmasi jurnal
     */
    public function confirm()
    {
        $this->update([
            'is_confirmed' => true,
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

        if (!$this->rekening_air_items) {
            return $entries;
        }

        // Entry untuk setiap item berdasarkan position (D/K)
        foreach ($this->rekening_air_items as $item) {
            $jumlah = floatval($item['jumlah'] ?? 0);
            $position = $item['position'] ?? 'D'; // D = Debit, K = Kredit

            $entries[] = [
                'tanggal' => $this->tanggal,
                'bukti' => $this->bukti,
                'rekening_id' => $item['rekening'] ?? null,
                'nomor_bantu_id' => $item['nomor_bantu'] ?? null,
                'debit' => $position === 'D' ? $jumlah : 0,
                'kredit' => $position === 'K' ? $jumlah : 0,
                'keterangan' => $this->keterangan,
                'kode_proyek_id' => $item['kode_proyek'] ?? null,
                'reff' => 2, // Untuk Jurnal Rekening Air
            ];
        }

        return $entries;
    }
}
