<?php

namespace App\Models;

use Database\Factories\PageAttachmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PageAttachment extends Model
{
    /** @use HasFactory<PageAttachmentFactory> */
    use HasFactory;

    protected $fillable = [
        'page_id',
        'disk',
        'path',
        'original_filename',
        'mime_type',
        'kind',
        'size',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Page, $this>
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    public function formattedSize(): string
    {
        if ($this->size < 1024) {
            return "{$this->size} B";
        }

        if ($this->size < 1024 * 1024) {
            return round($this->size / 1024, 1).' KB';
        }

        return round($this->size / (1024 * 1024), 1).' MB';
    }
}
