<?php

use App\Models\Setting;

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
