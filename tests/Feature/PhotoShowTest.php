<?php

use App\Models\Album;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('photos');
});

test('it shows a photo\'s details', function () {
    $album = Album::factory()->create();
    $photo = Photo::factory()->for($album)->processed()->create([
        'title' => 'Sonnenuntergang',
        'camera_make' => 'Fujifilm',
        'camera_model' => 'X-T5',
        'width' => 1920,
        'height' => 1080,
    ]);

    $response = $this->get(route('albums.photos.show', [$album, $photo]));

    $response->assertOk()
        ->assertSee('Sonnenuntergang')
        ->assertSee('Fujifilm')
        ->assertSee('1920 × 1080 px');
});

test('it returns 404 when the photo does not belong to the album', function () {
    $album = Album::factory()->create();
    $otherAlbum = Album::factory()->create();
    $photo = Photo::factory()->for($otherAlbum)->processed()->create();

    $response = $this->get(route('albums.photos.show', [$album, $photo]));

    $response->assertNotFound();
});

test('it returns 404 for an unprocessed photo', function () {
    $album = Album::factory()->create();
    $photo = Photo::factory()->for($album)->create(['processed_at' => null]);

    $response = $this->get(route('albums.photos.show', [$album, $photo]));

    $response->assertNotFound();
});

test('it returns 404 for a photo in a locked private album', function () {
    $album = Album::factory()->private()->create();
    $photo = Photo::factory()->for($album)->processed()->create();

    $response = $this->get(route('albums.photos.show', [$album, $photo]));

    $response->assertNotFound();
});

test('it shows a photo in a private album once unlocked in session', function () {
    $album = Album::factory()->private()->create();
    $photo = Photo::factory()->for($album)->processed()->create(['title' => 'Geheimfoto']);

    $response = $this->withSession([$album->unlockSessionKey() => true])
        ->get(route('albums.photos.show', [$album, $photo]));

    $response->assertOk()->assertSee('Geheimfoto');
});

test('it shows open graph tags for the photo', function () {
    $album = Album::factory()->create(['title' => 'Sommerurlaub']);
    $photo = Photo::factory()->for($album)->processed()->create(['title' => 'Sonnenuntergang', 'notes' => 'Am Strand aufgenommen.']);

    $response = $this->get(route('albums.photos.show', [$album, $photo]));

    $response->assertOk()
        ->assertSee('<meta property="og:title" content="Sonnenuntergang">', false)
        ->assertSee('<meta property="og:description" content="Am Strand aufgenommen.">', false)
        ->assertSee(
            '<meta property="og:image" content="'.route('albums.photos.display', [$album, $photo]).'">',
            false
        );
});

test('it falls back to the album title for open graph when the photo has no title', function () {
    $album = Album::factory()->create(['title' => 'Sommerurlaub']);
    $photo = Photo::factory()->for($album)->processed()->create(['title' => null]);

    $response = $this->get(route('albums.photos.show', [$album, $photo]));

    $response->assertOk()->assertSee('<meta property="og:title" content="Sommerurlaub">', false);
});

test('it links to the previous and next photo by sort order', function () {
    $album = Album::factory()->create();
    $first = Photo::factory()->for($album)->processed()->create(['sort_order' => 0]);
    $second = Photo::factory()->for($album)->processed()->create(['sort_order' => 1]);
    $third = Photo::factory()->for($album)->processed()->create(['sort_order' => 2]);

    $response = $this->get(route('albums.photos.show', [$album, $second]));

    $response->assertOk()
        ->assertSee(route('albums.photos.show', [$album, $first]), false)
        ->assertSee(route('albums.photos.show', [$album, $third]), false);
});

test('it records a view for a guest visitor', function () {
    $album = Album::factory()->create();
    $photo = Photo::factory()->for($album)->processed()->create();

    $this->get(route('albums.photos.show', [$album, $photo]));

    expect(views($photo)->count())->toBe(1);
});

test('it does not record a view for an authenticated user', function () {
    $album = Album::factory()->create();
    $photo = Photo::factory()->for($album)->processed()->create();

    $this->actingAs(User::factory()->create())->get(route('albums.photos.show', [$album, $photo]));

    expect(views($photo)->count())->toBe(0);
});
