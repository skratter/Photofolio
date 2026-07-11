<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

test('the branding url helpers return null when nothing has been uploaded', function () {
    $setting = Setting::current();

    expect($setting->faviconUrl())->toBeNull()
        ->and($setting->logoLightUrl())->toBeNull()
        ->and($setting->logoDarkUrl())->toBeNull();
});

test('the branding url helpers resolve the public disk url once a path is set', function () {
    Storage::fake('public');

    $setting = Setting::current();
    $setting->update([
        'favicon_path' => 'branding/favicon.svg',
        'logo_light_path' => 'branding/logo-light.svg',
        'logo_dark_path' => 'branding/logo-dark.svg',
    ]);

    expect($setting->faviconUrl())->toBe(Storage::disk('public')->url('branding/favicon.svg'))
        ->and($setting->logoLightUrl())->toBe(Storage::disk('public')->url('branding/logo-light.svg'))
        ->and($setting->logoDarkUrl())->toBe(Storage::disk('public')->url('branding/logo-dark.svg'));
});

test('current creates a row with sensible defaults on first access', function () {
    expect(Setting::query()->count())->toBe(0);

    $setting = Setting::current();

    expect(Setting::query()->count())->toBe(1)
        ->and($setting->homepage_photo_count)->toBe(12)
        ->and($setting->homepage_rotate_seconds)->toBe(8)
        ->and($setting->slideshow_autoplay_seconds)->toBe(3)
        ->and($setting->album_photos_per_page)->toBe(30);
});

test('current reuses the same row on subsequent calls instead of creating another', function () {
    Setting::current();
    Setting::current();

    expect(Setting::query()->count())->toBe(1);
});

test('forgetCached forces the next call to read fresh from the database', function () {
    $setting = Setting::current();
    $setting->update(['album_photos_per_page' => 50]);

    // Without forgetting, current() would still return the stale in-memory
    // instance from before the update above.
    Setting::forgetCached();

    expect(Setting::current()->album_photos_per_page)->toBe(50);
});
