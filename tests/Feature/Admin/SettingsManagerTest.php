<?php

use App\Livewire\Admin\SettingsManager;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
});

test('guests are redirected to login', function () {
    $response = $this->get(route('admin.settings'));

    $response->assertRedirect(route('login'));
});

test('it shows the current settings', function () {
    $user = User::factory()->create();
    Setting::current()->update(['homepage_photo_count' => 20]);

    Livewire::actingAs($user)
        ->test(SettingsManager::class)
        ->assertSet('form.homepage_photo_count', 20);
});

test('it updates the settings', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(SettingsManager::class)
        ->set('form.site_name', 'Meine Fotoseite')
        ->set('form.homepage_meta_description', 'Fotografie aus aller Welt.')
        ->set('form.homepage_photo_count', 20)
        ->set('form.homepage_rotate_seconds', 10)
        ->set('form.slideshow_autoplay_seconds', 5)
        ->set('form.album_photos_per_page', 50)
        ->set('form.masonry_columns', 4)
        ->set('form.social_instagram_url', 'https://www.instagram.com/example/')
        ->set('form.social_linkedin_url', 'https://www.linkedin.com/in/example/')
        ->set('form.social_facebook_url', 'https://www.facebook.com/example/')
        ->set('form.social_flickr_url', 'https://www.flickr.com/photos/example/')
        ->set('form.social_x_url', 'https://x.com/example/')
        ->set('form.social_youtube_url', 'https://www.youtube.com/@example/')
        ->set('form.social_pinterest_url', 'https://www.pinterest.com/example/')
        ->set('form.analytics_own_domains', "example.com\nwww.example.com")
        ->call('save')
        ->assertHasNoErrors();

    $setting = Setting::current();

    expect($setting->site_name)->toBe('Meine Fotoseite')
        ->and($setting->homepage_meta_description)->toBe('Fotografie aus aller Welt.')
        ->and($setting->homepage_photo_count)->toBe(20)
        ->and($setting->homepage_rotate_seconds)->toBe(10)
        ->and($setting->slideshow_autoplay_seconds)->toBe(5)
        ->and($setting->album_photos_per_page)->toBe(50)
        ->and($setting->masonry_columns)->toBe(4)
        ->and($setting->social_instagram_url)->toBe('https://www.instagram.com/example/')
        ->and($setting->social_linkedin_url)->toBe('https://www.linkedin.com/in/example/')
        ->and($setting->social_facebook_url)->toBe('https://www.facebook.com/example/')
        ->and($setting->social_flickr_url)->toBe('https://www.flickr.com/photos/example/')
        ->and($setting->social_x_url)->toBe('https://x.com/example/')
        ->and($setting->social_youtube_url)->toBe('https://www.youtube.com/@example/')
        ->and($setting->social_pinterest_url)->toBe('https://www.pinterest.com/example/')
        ->and($setting->ownDomainsList())->toBe(['example.com', 'www.example.com']);
});

test('it validates the settings', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(SettingsManager::class)
        ->set('form.homepage_photo_count', 0)
        ->set('form.social_instagram_url', 'not-a-url')
        ->call('save')
        ->assertHasErrors(['form.homepage_photo_count', 'form.social_instagram_url']);
});

test('leaving a social link empty removes it', function () {
    $user = User::factory()->create();
    Setting::current()->update(['social_instagram_url' => 'https://www.instagram.com/example/']);

    Livewire::actingAs($user)
        ->test(SettingsManager::class)
        ->set('form.social_instagram_url', '')
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::current()->social_instagram_url)->toBeNull();
});

test('uploading branding files stores them on the public disk and records their paths', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(SettingsManager::class)
        ->set('form.favicon', UploadedFile::fake()->create('favicon.svg', 10, 'image/svg+xml'))
        ->set('form.logoLight', UploadedFile::fake()->image('logo-black.png'))
        ->set('form.logoDark', UploadedFile::fake()->image('logo-white.png'))
        ->call('save')
        ->assertHasNoErrors();

    $setting = Setting::current();

    expect($setting->favicon_path)->not->toBeNull()
        ->and($setting->logo_light_path)->not->toBeNull()
        ->and($setting->logo_dark_path)->not->toBeNull();

    Storage::disk('public')->assertExists($setting->favicon_path);
    Storage::disk('public')->assertExists($setting->logo_light_path);
    Storage::disk('public')->assertExists($setting->logo_dark_path);
});

test('uploading a new favicon replaces and deletes the previous file', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)->test(SettingsManager::class)
        ->set('form.favicon', UploadedFile::fake()->image('first.png'))
        ->call('save');

    $firstPath = Setting::current()->favicon_path;

    $component->set('form.favicon', UploadedFile::fake()->image('second.png'))
        ->call('save');

    $secondPath = Setting::current()->favicon_path;

    expect($secondPath)->not->toBe($firstPath);
    Storage::disk('public')->assertMissing($firstPath);
    Storage::disk('public')->assertExists($secondPath);
});

test('removing the favicon clears the path and deletes the file', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(SettingsManager::class)
        ->set('form.favicon', UploadedFile::fake()->image('favicon.png'))
        ->call('save');

    $path = Setting::current()->favicon_path;

    Livewire::actingAs($user)
        ->test(SettingsManager::class)
        ->call('removeFavicon');

    expect(Setting::current()->favicon_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

test('removing the light and dark logos clears their paths', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(SettingsManager::class)
        ->set('form.logoLight', UploadedFile::fake()->image('logo-black.png'))
        ->set('form.logoDark', UploadedFile::fake()->image('logo-white.png'))
        ->call('save');

    Livewire::actingAs($user)
        ->test(SettingsManager::class)
        ->call('removeLogoLight')
        ->call('removeLogoDark');

    expect(Setting::current()->logo_light_path)->toBeNull()
        ->and(Setting::current()->logo_dark_path)->toBeNull();
});
