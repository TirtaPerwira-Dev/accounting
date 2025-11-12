<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class OpeningBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'kelompok_id',
        'rekening_id',
        'nomor_bantu_id',
        'as_of_date',
        'debit_balance',
        'credit_balance',
        'description',
        'is_confirmed',
        'created_by',
        'confirmed_by',
        'confirmed_at',
    ];

    protected $casts = [
        'as_of_date' => 'date',
        'debit_balance' => 'decimal:2',
        'credit_balance' => 'decimal:2',
        'is_confirmed' => 'boolean',
        'confirmed_at' => 'datetime',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    // Accessors
    public function getBalanceAttribute(): float
    {
        return $this->debit_balance > 0 ? $this->debit_balance : $this->credit_balance;
    }

    public function getBalanceTypeAttribute(): string
    {
        return $this->debit_balance > 0 ? 'debit' : 'credit';
    }

    // Scopes
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeAsOfDate($query, $date)
    {
        return $query->where('as_of_date', $date);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('is_confirmed', true);
    }

    public function scopePending($query)
    {
        return $query->where('is_confirmed', false);
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

    // Business Logic
    public function confirm($userId = null): bool
    {
        if ($this->is_confirmed) {
            return false;
        }

        return $this->update([
            'is_confirmed' => true,
            'confirmed_by' => $userId ?? Auth::id(),
            'confirmed_at' => now(),
        ]);
    }

    public function unconfirm(): bool
    {
        if (!$this->is_confirmed) {
            return false;
        }

        return $this->update([
            'is_confirmed' => false,
            'confirmed_by' => null,
            'confirmed_at' => null,
        ]);
    }

    // Validation
    public function getIsValidAttribute(): bool
    {
        // Either debit or credit must be > 0, but not both
        return ($this->debit_balance > 0 && $this->credit_balance == 0) ||
            ($this->credit_balance > 0 && $this->debit_balance == 0);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($balance) {
            // Ensure numeric values
            $balance->debit_balance = is_numeric($balance->debit_balance) ? floatval($balance->debit_balance) : 0;
            $balance->credit_balance = is_numeric($balance->credit_balance) ? floatval($balance->credit_balance) : 0;

            // Ensure only debit OR credit, not both
            if ($balance->debit_balance > 0 && $balance->credit_balance > 0) {
                throw new \InvalidArgumentException('Opening balance cannot have both debit and credit amounts');
            }

            if ($balance->debit_balance == 0 && $balance->credit_balance == 0) {
                throw new \InvalidArgumentException('Opening balance must have either debit or credit amount');
            }

            // Set created_by if not set
            if (empty($balance->created_by)) {
                $balance->created_by = Auth::id();
            }
        });
    }
}
