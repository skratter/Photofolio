<?php

use App\Models\Album;
use App\Models\Photo;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    Storage::fake('photos');
});

test('downloads a single-photo album as the original file', function () {
    $album = Album::factory()->create();
    $photo = Photo::factory()->for($album)->processed()->create(['title' => 'Strand']);

    File::ensureDirectoryExists($photo->directoryPath());
    File::put($photo->originalPath(), 'fake-image-bytes');

    $response = $this->get(route('albums.download', $album));

    $response->assertDownload('strand.jpg');
});

test('downloads a multi-photo album as a zip', function () {
    $album = Album::factory()->create(['title' => 'Sommerurlaub']);
    $photo1 = Photo::factory()->for($album)->processed()->create();
    $photo2 = Photo::factory()->for($album)->processed()->create();

    foreach ([$photo1, $photo2] as $photo) {
        File::ensureDirectoryExists($photo->directoryPath());
        File::put($photo->originalPath(), 'fake-image-bytes');
    }

    $response = $this->get(route('albums.download', $album));

    $response->assertDownload(Str::slug($album->title).'.zip');
});

test('returns 404 for a locked private album', function () {
    $album = Album::factory()->private()->create();
    $album->setPassword('secret123');
    $album->save();
    Photo::factory()->for($album)->processed()->create();

    $response = $this->get(route('albums.download', $album));

    $response->assertNotFound();
});

test('returns 404 once unlocked if downloads are disabled on the album', function () {
    $album = Album::factory()->create(['downloads_enabled' => false]);
    Photo::factory()->for($album)->processed()->create();

    $response = $this->get(route('albums.download', $album));

    $response->assertNotFound();
});

test('downloads a private album once unlocked in session', function () {
    $album = Album::factory()->private()->create();
    $album->setPassword('secret123');
    $album->save();
    $photo = Photo::factory()->for($album)->processed()->create(['title' => 'Geheimfoto']);

    File::ensureDirectoryExists($photo->directoryPath());
    File::put($photo->originalPath(), 'fake-image-bytes');

    $response = $this->withSession([$album->unlockSessionKey() => true])
        ->get(route('albums.download', $album));

    $response->assertDownload('geheimfoto.jpg');
});

test('returns 404 for an album with no processed photos', function () {
    $album = Album::factory()->create();

    $response = $this->get(route('albums.download', $album));

    $response->assertNotFound();
});
