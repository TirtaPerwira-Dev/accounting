<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'journal_id',
        'kelompok_id',
        'rekening_id',
        'nomor_bantu_id',
        'debit',
        'credit',
        'description',
        'line_number',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    // Relationships
    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    /**
     * SAKEP Relationships
     */
    public function kelompok(): BelongsTo
    {
        return $this->belongsTo(Kelompok::class, 'kelompok_id');
    }

    public function rekening(): BelongsTo
    {
        return $this->belongsTo(Rekening::class, 'rekening_id');
    }

    public function nomorBantu(): BelongsTo
    {
        return $this->belongsTo(NomorBantu::class, 'nomor_bantu_id');
    }

    // Accessors
    public function getAmountAttribute(): float
    {
        return $this->debit > 0 ? $this->debit : $this->credit;
    }

    public function getTypeAttribute(): string
    {
        return $this->debit > 0 ? 'debit' : 'credit';
    }

    // Validation
    public function getIsValidAttribute(): bool
    {
        // Either debit or credit must be > 0, but not both
        return ($this->debit > 0 && $this->credit == 0) ||
            ($this->credit > 0 && $this->debit == 0);
    }

    // Scopes
    public function scopeDebits($query)
    {
        return $query->where('debit', '>', 0);
    }

    public function scopeCredits($query)
    {
        return $query->where('credit', '>', 0);
    }

    /**
     * SAKEP Scopes
     */
    public function scopeByKelompok($query, $kelompokId)
    {
        return $query->where('kelompok_id', $kelompokId);
    }

    public function scopeByRekening($query, $rekeningId)
    {
        return $query->where('rekening_id', $rekeningId);
    }

    public function scopeByNomorBantu($query, $nomorBantuId)
    {
        return $query->where('nomor_bantu_id', $nomorBantuId);
    }

    /**
     * Get full SAKEP code from relations
     */
    public function getSakepCodeAttribute(): string
    {
        if ($this->nomor_bantu_id && $this->nomorBantu) {
            return $this->nomorBantu->rekening->kelompok->no_kel .
                $this->nomorBantu->rekening->no_rek .
                $this->nomorBantu->no_bantu;
        }

        if ($this->rekening_id && $this->rekening) {
            return $this->rekening->kelompok->no_kel . $this->rekening->no_rek;
        }

        if ($this->kelompok_id && $this->kelompok) {
            return $this->kelompok->no_kel;
        }

        return '';
    }

    /**
     * Get account name from SAKEP
     */
    public function getAccountNameAttribute(): string
    {
        if ($this->nomor_bantu_id && $this->nomorBantu) {
            return $this->nomorBantu->nm_bantu;
        }

        if ($this->rekening_id && $this->rekening) {
            return $this->rekening->nama_rek;
        }

        if ($this->kelompok_id && $this->kelompok) {
            return $this->kelompok->nama_kel;
        }

        return '';
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($detail) {
            // Ensure line number is set
            if (empty($detail->line_number)) {
                $maxLine = static::where('journal_id', $detail->journal_id)->max('line_number') ?? 0;
                $detail->line_number = $maxLine + 1;
            }

            // Convert to numeric values
            $detail->debit = is_numeric($detail->debit) ? floatval($detail->debit) : 0;
            $detail->credit = is_numeric($detail->credit) ? floatval($detail->credit) : 0;

            // Ensure only debit OR credit, not both
            if ($detail->debit > 0 && $detail->credit > 0) {
                throw new \InvalidArgumentException('Journal detail cannot have both debit and credit amounts');
            }

            if ($detail->debit == 0 && $detail->credit == 0) {
                throw new \InvalidArgumentException('Journal detail must have either debit or credit amount');
            }
        });
    }
}
