<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class JurnalPembelian extends Model
{
    protected $table = 'jurnal_pembelians';

    protected $fillable = [
        'no_reff',
        'tanggal',
        'bukti',
        'rp',
        'keterangan',
        'pembelian_items', // JSON data untuk repeater (backup)
        'kelompok_kredit_id',
        'rekening_kredit_id',
        'nomor_bantu_kredit_id',
        'nama_nomor_bantu_kredit', // Manual input nama
        'data_k',
        'data_d', // Data untuk rekening AT (Aktiva Tetap)
        'kode_proyek_id',
        'company_id',
        'is_confirmed',
        'confirmed_by',
        'confirmed_at',
        // Fields untuk item individual
        'bukti_item',
        'keterangan_item',
        'jumlah_item',
        'kelompok_debit_id',
        'rekening_debit_id',
        'nomor_bantu_debit_id',
        'group_transaksi',
        'item_sequence',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'rp' => 'decimal:2',
        'pembelian_items' => 'array', // Cast JSON to array (backup)
        'jumlah_item' => 'decimal:2',
        'is_confirmed' => 'boolean',
        'confirmed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Sementara comment untuk debug no_reff issue
            // if (empty($model->no_reff) || is_numeric($model->no_reff)) {
            //     $model->no_reff = $model->generateNoReff();
            // }

            if (empty($model->company_id)) {
                $model->company_id = 1; // Default company
            }
        });
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

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    // Relasi untuk akun debit item
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

    // === METHODS ===

    /**
     * Generate nomor referensi format: 1-1/2024
     */
    public function generateNoReff(): string
    {
        $year = Carbon::now()->year;

        // Get last number for this year - cari yang berbentuk format lengkap
        $lastJurnal = self::where('no_reff', 'LIKE', "1-_/{$year}")
            ->where('no_reff', 'NOT LIKE', '%-%-%') // Avoid malformed formats
            ->orderBy('created_at', 'desc')
            ->first();

        $nextNumber = 1;

        if ($lastJurnal && $lastJurnal->no_reff) {
            // Extract number from format like "1-5/2024"
            if (preg_match('/^1-(\d+)\/\d{4}$/', $lastJurnal->no_reff, $matches)) {
                $nextNumber = intval($matches[1]) + 1;
            }
        }

        return "1-{$nextNumber}/{$year}";
    }

    /**
     * Get kode SAKEP for kredit account
     */
    public function getKodeSakepKreditAttribute(): string
    {
        if (!$this->kelompokKredit || !$this->rekeningKredit || !$this->nomorBantuKredit) {
            return '-';
        }

        return $this->kelompokKredit->no_kel .
            $this->rekeningKredit->no_rek .
            str_pad($this->nomorBantuKredit->no_bantu, 2, '0', STR_PAD_LEFT);
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
        if (!$this->kelompokDebit || !$this->rekeningDebit || !$this->nomorBantuDebit) {
            return '-';
        }

        return $this->kelompokDebit->no_kel .
            $this->rekeningDebit->no_rek .
            str_pad($this->nomorBantuDebit->no_bantu, 2, '0', STR_PAD_LEFT);
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
            return $this->jumlah_item ?? 0; // Single item
        }

        return self::where('group_transaksi', $this->group_transaksi)
            ->sum('jumlah_item');
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

            $firstItem = $items->first()->keterangan_item ?: 'Item pembelian';

            if ($items->count() === 1) {
                return $firstItem;
            }

            return $firstItem . " (+ " . ($items->count() - 1) . " item lainnya)";
        }

        return $this->keterangan_item ?: 'Item pembelian';
    }

    /**
     * Konfirmasi jurnal
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
