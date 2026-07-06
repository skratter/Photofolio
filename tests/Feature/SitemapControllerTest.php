<?php

use App\Models\Album;
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
