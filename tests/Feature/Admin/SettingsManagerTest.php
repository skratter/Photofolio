<?php

use App\Livewire\Admin\SettingsManager;
use App\Models\Setting;
use App\Models\User;
use Livewire\Livewire;

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
        ->set('form.homepage_photo_count', 20)
        ->set('form.homepage_rotate_seconds', 10)
        ->set('form.slideshow_autoplay_seconds', 5)
        ->set('form.album_photos_per_page', 50)
        ->set('form.masonry_columns', 4)
        ->set('form.social_instagram_url', 'https://www.instagram.com/example/')
        ->set('form.social_linkedin_url', 'https://www.linkedin.com/in/example/')
        ->set('form.analytics_own_domains', "example.com\nwww.example.com")
        ->call('save')
        ->assertHasNoErrors();

    $setting = Setting::current();

    expect($setting->site_name)->toBe('Meine Fotoseite')
        ->and($setting->homepage_photo_count)->toBe(20)
        ->and($setting->homepage_rotate_seconds)->toBe(10)
        ->and($setting->slideshow_autoplay_seconds)->toBe(5)
        ->and($setting->album_photos_per_page)->toBe(50)
        ->and($setting->masonry_columns)->toBe(4)
        ->and($setting->social_instagram_url)->toBe('https://www.instagram.com/example/')
        ->and($setting->social_linkedin_url)->toBe('https://www.linkedin.com/in/example/')
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
