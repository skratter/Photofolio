<?php

use App\Models\Album;
use App\Models\Photo;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('photos');
});

test('serves the thumb variant of a photo in a public album', function () {
    $album = Album::factory()->create();
    $photo = Photo::factory()->processed()->create(['album_id' => $album->id]);

    File::ensureDirectoryExists($photo->directoryPath());
    File::put($photo->thumbPath(), 'fake-thumb-contents');

    $response = $this->get(route('albums.photos.thumb', [$album, $photo]));

    $response->assertOk();
});

test('returns 404 for a photo in a private album', function () {
    $album = Album::factory()->private()->create();
    $photo = Photo::factory()->processed()->create(['album_id' => $album->id]);

    File::ensureDirectoryExists($photo->directoryPath());
    File::put($photo->thumbPath(), 'fake-thumb-contents');

    $response = $this->get(route('albums.photos.thumb', [$album, $photo]));

    $response->assertNotFound();
});

test('returns 404 for an unprocessed photo', function () {
    $album = Album::factory()->create();
    $photo = Photo::factory()->create(['album_id' => $album->id, 'processed_at' => null]);

    $response = $this->get(route('albums.photos.thumb', [$album, $photo]));

    $response->assertNotFound();
});

test('serves the photo from a private album once unlocked in session', function () {
    $album = Album::factory()->private()->create();
    $photo = Photo::factory()->processed()->create(['album_id' => $album->id]);

    File::ensureDirectoryExists($photo->directoryPath());
    File::put($photo->thumbPath(), 'fake-thumb-contents');

    $response = $this->withSession([$album->unlockSessionKey() => true])
        ->get(route('albums.photos.thumb', [$album, $photo]));

    $response->assertOk();
});

test('returns 404 when the photo does not belong to the album', function () {
    $album = Album::factory()->create();
    $otherAlbum = Album::factory()->create();
    $photo = Photo::factory()->processed()->create(['album_id' => $otherAlbum->id]);

    $response = $this->get(route('albums.photos.thumb', [$album, $photo]));

    $response->assertNotFound();
});
