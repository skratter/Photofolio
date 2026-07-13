<?php

use App\Models\Album;
use App\Models\Page;
use App\Models\Photo;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('photos');
});

test('it includes the homepage', function () {
    $response = $this->get(route('sitemap'));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee(url('/'), false);
});

test('it includes public albums but not private ones', function () {
    $public = Album::factory()->create(['visibility' => 'public', 'slug' => 'urlaub']);
    $private = Album::factory()->create(['visibility' => 'private', 'slug' => 'privat']);

    $response = $this->get(route('sitemap'));

    $response->assertOk()
        ->assertSee(route('albums.show', $public->slug), false)
        ->assertDontSee(route('albums.show', $private->slug), false);
});

test('it includes photos of public albums but not private ones', function () {
    $publicAlbum = Album::factory()->create(['visibility' => 'public', 'slug' => 'urlaub']);
    $publicPhoto = Photo::factory()->for($publicAlbum)->processed()->create();
    $privateAlbum = Album::factory()->private()->create(['slug' => 'privat']);
    $privatePhoto = Photo::factory()->for($privateAlbum)->processed()->create();

    $response = $this->get(route('sitemap'));

    $response->assertOk()
        ->assertSee(route('albums.photos.show', [$publicAlbum, $publicPhoto]), false)
        ->assertDontSee(route('albums.photos.show', [$privateAlbum, $privatePhoto]), false);
});

test('it includes published pages but not drafts', function () {
    $published = Page::factory()->create(['slug' => 'impressum', 'status' => 'published']);
    $draft = Page::factory()->create(['slug' => 'entwurf', 'status' => 'draft']);

    $response = $this->get(route('sitemap'));

    $response->assertOk()
        ->assertSee(route('pages.show', $published->slug), false)
        ->assertDontSee(route('pages.show', $draft->slug), false);
});

test('an album entry lists each of its processed photos as an image sitemap entry', function () {
    $album = Album::factory()->create(['visibility' => 'public']);
    $photo = Photo::factory()->for($album)->processed()->create();
    $unprocessed = Photo::factory()->for($album)->create(['processed_at' => null]);

    $response = $this->get(route('sitemap'));

    $response->assertOk()
        ->assertSee('<image:loc>'.route('albums.photos.display', [$album, $photo]).'</image:loc>', false)
        ->assertDontSee(route('albums.photos.display', [$album, $unprocessed]), false);
});

test('an individual photo page lists its own image sitemap entry', function () {
    $album = Album::factory()->create(['visibility' => 'public']);
    $photo = Photo::factory()->for($album)->processed()->create();

    $response = $this->get(route('sitemap'));

    $response->assertOk()
        ->assertSee('<image:loc>'.route('albums.photos.display', [$album, $photo]).'</image:loc>', false);
});
