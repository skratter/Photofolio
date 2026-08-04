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
     * @return array{width: int, height: int}|null
     */
    public function logoLightDimensions(): ?array
    {
        return $this->logo_light_path ? $this->brandingDimensions($this->logo_light_path) : null;
    }

    /**
     * @return array{width: int, height: int}|null
     */
    public function logoDarkDimensions(): ?array
    {
        return $this->logo_dark_path ? $this->brandingDimensions($this->logo_dark_path) : null;
    }

    /**
     * Intrinsic pixel dimensions of an uploaded branding file, so <img> tags
     * can carry width/height attributes - the browser then reserves the
     * correct aspect ratio before the file has loaded, instead of shifting
     * the layout once it arrives. Returns null (caller then omits the
     * attributes, same as before) if the file is missing or its dimensions
     * can't be determined.
     *
     * @return array{width: int, height: int}|null
     */
    private function brandingDimensions(string $path): ?array
    {
        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        $absolutePath = Storage::disk('public')->path($path);

        if (str_ends_with(strtolower($path), '.svg')) {
            return $this->svgDimensions($absolutePath);
        }

        $size = @getimagesize($absolutePath);

        return $size ? ['width' => $size[0], 'height' => $size[1]] : null;
    }

    /**
     * @return array{width: int, height: int}|null
     */
    private function svgDimensions(string $absolutePath): ?array
    {
        $contents = @file_get_contents($absolutePath);

        if ($contents === false) {
            return null;
        }

        if (preg_match('/viewBox="[\d.\-]+\s+[\d.\-]+\s+([\d.]+)\s+([\d.]+)"/i', $contents, $matches)) {
            return ['width' => (int) round((float) $matches[1]), 'height' => (int) round((float) $matches[2])];
        }

        if (preg_match('/width="([\d.]+)(?:px)?"/i', $contents, $widthMatch)
            && preg_match('/height="([\d.]+)(?:px)?"/i', $contents, $heightMatch)) {
            return ['width' => (int) round((float) $widthMatch[1]), 'height' => (int) round((float) $heightMatch[1])];
        }

        return null;
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
