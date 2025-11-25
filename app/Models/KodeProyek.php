<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KodeProyek extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode',
        'name',
        'tahun',
        'ket',
        'user_id',
    ];

    protected $casts = [
        'tahun' => 'integer',
    ];

    /**
     * Get the user that owns the KodeProyek
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
