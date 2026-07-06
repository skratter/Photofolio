<?php

namespace App\Models;

use CyrildeWit\EloquentViewable\Contracts\Viewable;
use CyrildeWit\EloquentViewable\InteractsWithViews;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model implements Viewable
{
    /** @use HasFactory<PageFactory> */
    use HasFactory;

    use InteractsWithViews;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'meta_description',
        'type',
        'status',
        'show_in_navigation',
        'sort_order',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'show_in_navigation' => 'boolean',
            'sort_order' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public static function forSlug(string $slug): self
    {
        return static::firstOrCreate(['slug' => $slug]);
    }

    /**
     * @return HasMany<PageAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(PageAttachment::class);
    }

    public function isDeletable(): bool
    {
        return $this->type !== 'legal';
    }

    /**
     * @param  Builder<Page>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', 'published');
    }

    /**
     * @param  Builder<Page>  $query
     */
    public function scopeInNavigation(Builder $query): void
    {
        $query->published()->where('show_in_navigation', true);
    }
}
