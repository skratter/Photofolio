<?php

use App\Models\Album;
use App\Models\Photo;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('photos');
});

test('downloads a single photo from a public album', function () {
    $album = Album::factory()->create();
    $photo = Photo::factory()->for($album)->processed()->create(['title' => 'Strand']);

    File::ensureDirectoryExists($photo->directoryPath());
    File::put($photo->originalPath(), 'fake-image-bytes');

    $response = $this->get(route('albums.photos.download', [$album, $photo]));

    $response->assertDownload('strand.jpg');
});

test('returns 404 for a photo in a locked private album', function () {
    $album = Album::factory()->private()->create();
    $album->setPassword('secret123');
    $album->save();
    $photo = Photo::factory()->for($album)->processed()->create();

    File::ensureDirectoryExists($photo->directoryPath());
    File::put($photo->originalPath(), 'fake-image-bytes');

    $response = $this->get(route('albums.photos.download', [$album, $photo]));

    $response->assertNotFound();
});

test('returns 404 when downloads are disabled on the album', function () {
    $album = Album::factory()->create(['downloads_enabled' => false]);
    $photo = Photo::factory()->for($album)->processed()->create();

    File::ensureDirectoryExists($photo->directoryPath());
    File::put($photo->originalPath(), 'fake-image-bytes');

    $response = $this->get(route('albums.photos.download', [$album, $photo]));

    $response->assertNotFound();
});

test('returns 404 for an unprocessed photo', function () {
    $album = Album::factory()->create();
    $photo = Photo::factory()->for($album)->create(['processed_at' => null]);

    $response = $this->get(route('albums.photos.download', [$album, $photo]));

    $response->assertNotFound();
});

test('returns 404 when the photo does not belong to the album', function () {
    $album = Album::factory()->create();
    $otherAlbum = Album::factory()->create();
    $photo = Photo::factory()->for($otherAlbum)->processed()->create();

    File::ensureDirectoryExists($photo->directoryPath());
    File::put($photo->originalPath(), 'fake-image-bytes');

    $response = $this->get(route('albums.photos.download', [$album, $photo]));

    $response->assertNotFound();
});
