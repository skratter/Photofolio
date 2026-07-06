<?php

namespace App\Models;

use CyrildeWit\EloquentViewable\Contracts\Viewable;
use CyrildeWit\EloquentViewable\InteractsWithViews;
use Database\Factories\AlbumFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;

class Album extends Model implements Viewable
{
    /** @use HasFactory<AlbumFactory> */
    use HasFactory;

    use InteractsWithViews;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'cover_photo_id',
        'visibility',
        'sort_order',
        'is_homepage',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected function casts(): array
    {
        return [
            'is_homepage' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<Photo, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class)->orderBy('sort_order');
    }

    /**
     * @return BelongsTo<Photo, $this>
     */
    public function coverPhoto(): BelongsTo
    {
        return $this->belongsTo(Photo::class, 'cover_photo_id');
    }

    public function effectiveCoverPhoto(): ?Photo
    {
        return $this->coverPhoto ?? $this->photos()->orderBy('sort_order')->first();
    }

    public function isPrivate(): bool
    {
        return $this->visibility === 'private';
    }

    public function setPassword(string $plainPassword): void
    {
        $this->password_hash = Hash::make($plainPassword);
    }

    public function checkPassword(string $plainPassword): bool
    {
        return $this->password_hash !== null && Hash::check($plainPassword, $this->password_hash);
    }
}
