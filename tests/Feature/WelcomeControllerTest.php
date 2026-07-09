<?php

use App\Models\Album;
use App\Models\Page;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('photos');
});

test('it renders the welcome page', function () {
    $response = $this->get('/');

    $response->assertOk();
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

test('it records a view for a guest visitor', function () {
    $this->get('/');

    expect(views(Page::forSlug('welcome'))->count())->toBe(1);
});

test('it does not record a view for an authenticated user', function () {
    $this->actingAs(User::factory()->create())->get('/');

    expect(views(Page::forSlug('welcome'))->count())->toBe(0);
});
