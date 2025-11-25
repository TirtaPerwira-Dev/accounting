<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class Journal extends Model
{
    use HasFactory, SoftDeletes;

    // Constants for transaction types
    const TYPE_PENERIMAAN = 'penerimaan';
    const TYPE_PENGELUARAN = 'pengeluaran';

    protected $fillable = [
        'company_id',
        'transaction_date',
        'reference',
        'description',
        'transaction_type',
        'total_amount',
        'status',
        'created_by',
        'posted_by',
        'posted_at',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'posted_at' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(JournalDetail::class)->orderBy('line_number');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    // Scopes
    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopePenerimaan($query)
    {
        return $query->where('transaction_type', self::TYPE_PENERIMAAN);
    }

    public function scopePengeluaran($query)
    {
        return $query->where('transaction_type', self::TYPE_PENGELUARAN);
    }

    // Business Logic
    public function getTotalDebitAttribute(): float
    {
        return $this->details->sum('debit');
    }

    public function getTotalCreditAttribute(): float
    {
        return $this->details->sum('credit');
    }

    public function getIsBalancedAttribute(): bool
    {
        return abs($this->totalDebit - $this->totalCredit) < 0.01;
    }

    public function isBalanced(): bool
    {
        return $this->is_balanced;
    }

    public function canBePosted(): bool
    {
        $conditions = [
            'status_is_draft' => $this->status === 'draft',
            'has_details' => $this->details->isNotEmpty(),
            'is_balanced' => $this->isBalanced(),
        ];

        // Debug logging (remove in production)
        if (!array_product($conditions)) {
            Log::info('Journal cannot be posted', [
                'journal_id' => $this->id,
                'conditions' => $conditions,
                'total_debit' => $this->totalDebit,
                'total_credit' => $this->totalCredit,
                'details_count' => $this->details->count(),
            ]);
        }

        return $this->status === 'draft'
            && $this->details->isNotEmpty()
            && $this->isBalanced();
    }

    public function post(): bool
    {
        if (!$this->canBePosted()) {
            return false;
        }

        return DB::transaction(function () {
            $this->update([
                'status' => 'posted',
                'posted_by' => Auth::id(),
                'posted_at' => now(),
                'total_amount' => $this->totalDebit,
            ]);

            return true;
        });
    }

    public function reverse(): bool
    {
        if ($this->status !== 'posted') {
            return false;
        }

        return DB::transaction(function () {
            // Create reversal journal
            $reversalJournal = static::create([
                'company_id' => $this->company_id,
                'transaction_date' => now()->toDateString(),
                'reference' => $this->reference . '-REV',
                'description' => 'Reversal of: ' . $this->description,
                'created_by' => Auth::id(),
            ]);

            // Create reversed details
            foreach ($this->details as $detail) {
                $reversalJournal->details()->create([
                    'account_id' => $detail->account_id,
                    'debit' => $detail->credit, // Swap debit/credit
                    'credit' => $detail->debit,
                    'description' => 'Reversal: ' . $detail->description,
                    'line_number' => $detail->line_number,
                ]);
            }

            $reversalJournal->post();

            $this->update(['status' => 'reversed']);

            return true;
        });
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($journal) {
            if (empty($journal->reference)) {
                $journal->reference = static::generateReference($journal->company_id, $journal->transaction_type);
            }

            if (empty($journal->created_by)) {
                $journal->created_by = Auth::id();
            }
        });
    }

    public static function generateReference($companyId, $transactionType = self::TYPE_PENERIMAAN): string
    {
        return DB::transaction(function () use ($companyId, $transactionType) {
            // Different prefix for different transaction types
            $typePrefix = $transactionType === self::TYPE_PENERIMAAN ? 'KM' : 'KK'; // Kas Masuk / Kas Keluar
            $prefix = $typePrefix . '-' . now()->format('Ym') . '-';

            // Get the highest number for this month and company with row locking
            $lastJournal = static::where('company_id', $companyId)
                ->where('transaction_type', $transactionType)
                ->where('reference', 'like', $prefix . '%')
                ->orderBy('reference', 'desc')
                ->lockForUpdate()
                ->first();

            if ($lastJournal && $lastJournal->reference) {
                // Extract the number part and increment
                $lastNumber = substr($lastJournal->reference, strlen($prefix));
                $number = (int)$lastNumber + 1;
            } else {
                $number = 1;
            }

            // Generate reference with safety check
            $maxAttempts = 100;
            $attempts = 0;

            do {
                $reference = $prefix . str_pad($number, 3, '0', STR_PAD_LEFT);
                $exists = static::where('company_id', $companyId)
                    ->where('transaction_type', $transactionType)
                    ->where('reference', $reference)
                    ->exists();

                if ($exists) {
                    $number++;
                    $attempts++;
                }
            } while ($exists && $attempts < $maxAttempts);

            if ($attempts >= $maxAttempts) {
                throw new \Exception('Could not generate unique reference after ' . $maxAttempts . ' attempts');
            }

            return $reference;
        });
    }
}
