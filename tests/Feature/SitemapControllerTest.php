<?php

use App\Models\Album;
use App\Models\Page;
use App\Models\Photo;
use Illuminate\Support\Facades\Route;

test('it includes the homepage', function () {
    $response = $this->get(route('sitemap'));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee(url('/'), false);
});

test('it does not include public albums while no public album route exists', function () {
    Album::factory()->create(['visibility' => 'public', 'slug' => 'urlaub']);

    $response = $this->get(route('sitemap'));

    $response->assertOk()->assertDontSee('urlaub');
});

test('it includes public albums once a public album route exists', function () {
    Route::get('/albums/{slug}', fn () => '')->name('albums.show');
    Route::getRoutes()->refreshNameLookups();

    $public = Album::factory()->create(['visibility' => 'public', 'slug' => 'urlaub']);
    $private = Album::factory()->create(['visibility' => 'private', 'slug' => 'privat']);

    $response = $this->get(route('sitemap'));

    $response->assertOk()
        ->assertSee(route('albums.show', $public->slug), false)
        ->assertDontSee(route('albums.show', $private->slug), false);
});

test('it does not include photos while no public photo route exists', function () {
    $album = Album::factory()->create(['visibility' => 'public']);
    Photo::factory()->for($album)->create();

    $response = $this->get(route('sitemap'));

    $response->assertOk()->assertDontSeeText('/photos/');
});

test('it includes photos of public albums once a public photo route exists', function () {
    Route::get('/albums/{album}/photos/{photo}', fn () => '')->name('albums.photos.show');
    Route::getRoutes()->refreshNameLookups();

    $publicAlbum = Album::factory()->create(['visibility' => 'public', 'slug' => 'urlaub']);
    $publicPhoto = Photo::factory()->for($publicAlbum)->create();
    $privateAlbum = Album::factory()->create(['visibility' => 'private', 'slug' => 'privat']);
    $privatePhoto = Photo::factory()->for($privateAlbum)->create();

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
