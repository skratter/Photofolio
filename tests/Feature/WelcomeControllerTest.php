<?php

use App\Models\Album;
use App\Models\Page;
use App\Models\Photo;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('photos');
});

test('it renders the welcome page', function () {
    $response = $this->get('/');

    $response->assertOk();
});

test('it shows the configured site name in the header and placeholder', function () {
    Setting::current()->update(['site_name' => 'Meine Fotoseite']);

    $response = $this->get('/');

    $response->assertOk()->assertSeeText('Meine Fotoseite');
});

test('it shows the site name as text in the header when no logo is configured', function () {
    Setting::current()->update(['site_name' => 'Meine Fotoseite']);

    $response = $this->get('/');

    $response->assertOk()->assertSeeText('Meine Fotoseite');
});

test('it shows the configured light logo instead of the site name text', function () {
    Setting::current()->update(['logo_light_path' => 'branding/logo-light.svg']);

    $response = $this->get('/');

    $response->assertOk()->assertSee(Storage::disk('public')->url('branding/logo-light.svg'), false);
});

test('it shows a copyright notice with the current year and site name', function () {
    Setting::current()->update(['site_name' => 'Meine Fotoseite']);

    $response = $this->get('/');

    $response->assertOk()->assertSeeText('© '.now()->year.' Meine Fotoseite. Alle Rechte vorbehalten.');
});

test('it only shows social links that are configured', function () {
    Setting::current()->update([
        'social_instagram_url' => 'https://www.instagram.com/example/',
        'social_linkedin_url' => null,
        'social_facebook_url' => 'https://www.facebook.com/example/',
        'social_flickr_url' => null,
        'social_x_url' => null,
        'social_youtube_url' => null,
        'social_pinterest_url' => null,
    ]);

    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('https://www.instagram.com/example/', false)
        ->assertSee('https://www.facebook.com/example/', false)
        ->assertDontSee('linkedin.com')
        ->assertDontSee('flickr.com')
        ->assertDontSee('youtube.com')
        ->assertDontSee('pinterest.com');
});

test('social links are hidden by default until configured', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertDontSee('facebook.com')
        ->assertDontSee('flickr.com')
        ->assertDontSee('x.com')
        ->assertDontSee('youtube.com')
        ->assertDontSee('pinterest.com');
});

test('it shows the placeholder when no album is marked as the homepage', function () {
    $response = $this->get('/');

    $response->assertOk()->assertSeeText('soon');
});

test('it shows the homepage album\'s photos when one is set', function () {
    $album = Album::factory()->create(['title' => 'Lieblingsbilder', 'is_homepage' => true]);
    Photo::factory()->for($album)->processed()->create();

    $response = $this->get('/');

    $response->assertOk()->assertDontSeeText('soon');
});

test('it shows at most 12 random photos from a larger homepage album, with the rest as a rotation pool', function () {
    $album = Album::factory()->create(['is_homepage' => true]);
    Photo::factory()->for($album)->processed()->count(58)->create();

    $response = $this->get('/');
    $data = $response->original->getData();

    expect($data['homepagePhotos'])->toHaveCount(12)
        ->and($data['homepagePhotoPool'])->toHaveCount(46);
});

test('the random photo selection actually varies between requests', function () {
    // The photos() relation orders by sort_order by default - inRandomOrder()
    // appending RAND() as a mere tiebreaker after that (instead of replacing
    // it) would silently make every request return the exact same order,
    // which is the regression this guards against.
    $album = Album::factory()->create(['is_homepage' => true]);
    Photo::factory()->for($album)->processed()->count(50)->sequence(fn ($sequence) => ['sort_order' => $sequence->index])->create();

    $first = $this->get('/')->original->getData()['homepagePhotos']->pluck('id')->all();
    $second = $this->get('/')->original->getData()['homepagePhotos']->pluck('id')->all();

    expect($first)->not->toBe($second);
});

test('it does not show a private album even if it is marked as the homepage', function () {
    $album = Album::factory()->private()->create(['title' => 'Geheimalbum', 'is_homepage' => true]);
    Photo::factory()->for($album)->processed()->create();

    $response = $this->get('/');

    $response->assertOk()->assertDontSeeText('Geheimalbum')->assertSeeText('soon');
});

test('it records a view for a guest visitor', function () {
    $this->get('/');

    expect(views(Page::forSlug('welcome'))->count())->toBe(1);
});

test('it does not record a view for an authenticated user', function () {
    $this->actingAs(User::factory()->create())->get('/');

    expect(views(Page::forSlug('welcome'))->count())->toBe(0);
});
