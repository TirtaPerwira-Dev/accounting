<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'pembelian_items', // JSON data untuk repeater
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
    ];

    protected $casts = [
        'tanggal' => 'date',
        'rp' => 'decimal:2',
        'pembelian_items' => 'array', // Cast JSON to array
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

    // === RELATIONSHIPS ===

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function kodeProyek(): BelongsTo
    {
        return $this->belongsTo(KodeProyek::class);
    }

    // Relasi Akun Kredit (Hutang/Pembayaran)
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

    // === METHODS ===

    /**
     * Generate nomor referensi format: 1-1/2024
     */
    public function generateNoReff(): string
    {
        $year = Carbon::now()->year;

        // Get last number for this year
        $lastJurnal = self::where('no_reff', 'LIKE', "%-{$year}")
            ->orderBy('no_reff', 'desc')
            ->first();

        if ($lastJurnal) {
            // Extract number from format like "1-5/2024"
            $parts = explode('-', $lastJurnal->no_reff);
            if (count($parts) >= 2) {
                $numberPart = explode('/', $parts[1])[0];
                $nextNumber = intval($numberPart) + 1;
            } else {
                $nextNumber = 1;
            }
        } else {
            $nextNumber = 1;
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
     * Get total pembelian items
     */
    public function getTotalPembelianAttribute(): float
    {
        if (!$this->pembelian_items) return 0;

        $total = 0;
        foreach ($this->pembelian_items as $item) {
            $total += $item['jumlah'] ?? 0;
        }

        return $total;
    }

    /**
     * Get summary of pembelian items
     */
    public function getPembelianSummaryAttribute(): string
    {
        if (!$this->pembelian_items || count($this->pembelian_items) === 0) {
            return 'Tidak ada item';
        }

        $count = count($this->pembelian_items);
        $firstItem = $this->pembelian_items[0]['keterangan'] ?? 'Item pembelian';

        if ($count === 1) {
            return $firstItem;
        }

        return $firstItem . " (+ " . ($count - 1) . " item lainnya)";
    }

    /**
     * Get pembelian items with enhanced data
     */
    public function getPembelianItemsWithDetailsAttribute(): array
    {
        if (!$this->pembelian_items) return [];

        $items = [];
        foreach ($this->pembelian_items as $item) {
            $nomorBantu = NomorBantu::find($item['nomor_bantu_debit_id'] ?? null);

            $itemWithDetails = $item;
            $itemWithDetails['kode_sakep_debit'] = $nomorBantu ?
                $nomorBantu->rekening->kelompok->no_kel .
                $nomorBantu->rekening->no_rek .
                str_pad($nomorBantu->no_bantu, 2, '0', STR_PAD_LEFT) : '-';

            $itemWithDetails['nama_akun_debit'] = $nomorBantu?->nm_bantu ?? '-';

            $items[] = $itemWithDetails;
        }

        return $items;
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
