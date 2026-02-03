<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Dokumentasi extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'judul',
        'kategori',
        'deskripsi',
        'konten',
        'file_attachment',
        'urutan',
        'is_published',
        'is_manual_book',
        'created_by',
        'updated_by',
        'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_manual_book' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['judul', 'kategori', 'is_published'])
            ->setDescriptionForEvent(fn(string $eventName) => "Dokumentasi has been {$eventName}")
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeDokumentasi($query)
    {
        return $query->where('is_manual_book', false);
    }

    public function scopeManualBook($query)
    {
        return $query->where('is_manual_book', true);
    }
}
