<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Photo extends Model
{
    use HasFactory;

    protected $fillable = [
        'album_id',
        'title',
        'notes',
        'original_filename',
        'disk_path',
        'mime_type',
        'file_size',
        'width',
        'height',
        'camera_make',
        'camera_model',
        'lens',
        'aperture',
        'shutter_speed',
        'iso',
        'focal_length',
        'taken_at',
        'latitude',
        'longitude',
        'exif_raw',
        'sort_order',
        'visibility',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'taken_at' => 'datetime',
            'processed_at' => 'datetime',
            'exif_raw' => 'array',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'iso' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'file_size' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function album(): BelongsTo
    {
        return $this->belongsTo(Album::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function isProcessed(): bool
    {
        return $this->processed_at !== null;
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function displayPath(): string
    {
        return storage_path("app/photos/{$this->id}/display.webp");
    }

    public function thumbPath(): string
    {
        return storage_path("app/photos/{$this->id}/thumb.webp");
    }

    public function originalPath(): string
    {
        $extension = pathinfo($this->original_filename, PATHINFO_EXTENSION);

        return storage_path("app/photos/{$this->id}/original.{$extension}");
    }
}