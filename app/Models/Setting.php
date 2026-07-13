<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Setting extends Model
{
    protected static ?self $cached = null;

    protected $fillable = [
        'site_name',
        'homepage_meta_description',
        'favicon_path',
        'logo_light_path',
        'logo_dark_path',
        'homepage_photo_count',
        'homepage_rotate_seconds',
        'slideshow_autoplay_seconds',
        'album_photos_per_page',
        'social_instagram_url',
        'social_linkedin_url',
        'social_facebook_url',
        'social_flickr_url',
        'social_x_url',
        'social_youtube_url',
        'social_pinterest_url',
        'analytics_own_domains',
        'masonry_columns',
    ];

    protected function casts(): array
    {
        return [
            'homepage_photo_count' => 'integer',
            'homepage_rotate_seconds' => 'integer',
            'slideshow_autoplay_seconds' => 'integer',
            'album_photos_per_page' => 'integer',
            'masonry_columns' => 'integer',
        ];
    }

    /**
     * Site settings are a single row, created on first access - no seeder
     * needed. Memoized per-request since several unrelated places
     * (WelcomeController, AlbumShow, the photo-masonry component) each read
     * this on every request.
     *
     * Defaults are passed explicitly here rather than left to the
     * migration's column defaults: after an insert, Eloquent doesn't
     * re-fetch the row, so a model created via firstOrCreate([]) would have
     * these attributes as null in memory even though the database applied
     * its defaults just fine - only visible once something used them (e.g.
     * a null per-page value made forPage() emit "offset" without "limit",
     * which MySQL tolerates silently but SQLite rejects outright).
     */
    public static function current(): self
    {
        return static::$cached ??= static::query()->firstOrCreate([], [
            'site_name' => config('app.name'),
            'homepage_photo_count' => 12,
            'homepage_rotate_seconds' => 8,
            'slideshow_autoplay_seconds' => 3,
            'album_photos_per_page' => 30,
            'social_instagram_url' => 'https://www.instagram.com/skratter_com/',
            'social_linkedin_url' => 'https://www.linkedin.com/in/daniel-lauermann/',
            'analytics_own_domains' => implode("\n", [
                'skratter.com',
                'www.skratter.com',
                'skratter.net',
                'skratter.de',
                'www.skratter.net',
                'familie-wolf.berlin',
                'www.familie-wolf.berlin',
                'joshua-wolf.de',
                'www.joshua-wolf.de',
                'nudelwasser24.de',
            ]),
            'masonry_columns' => 3,
        ]);
    }

    public static function forgetCached(): void
    {
        static::$cached = null;
    }

    /**
     * Custom branding uploaded via the settings screen, so a Photofolio
     * installation isn't stuck with this project's own default favicon/logo
     * - falls back to null (callers fall back to the shipped defaults) when
     * nothing's been uploaded.
     */
    public function faviconUrl(): ?string
    {
        return $this->favicon_path ? Storage::disk('public')->url($this->favicon_path) : null;
    }

    public function logoLightUrl(): ?string
    {
        return $this->logo_light_path ? Storage::disk('public')->url($this->logo_light_path) : null;
    }

    public function logoDarkUrl(): ?string
    {
        return $this->logo_dark_path ? Storage::disk('public')->url($this->logo_dark_path) : null;
    }

    /**
     * @return list<string>
     */
    public function ownDomainsList(): array
    {
        $parts = preg_split('/[\r\n,]+/', (string) $this->analytics_own_domains);

        if ($parts === false) {
            return [];
        }

        return array_values(array_filter(array_map(trim(...), $parts)));
    }
}
