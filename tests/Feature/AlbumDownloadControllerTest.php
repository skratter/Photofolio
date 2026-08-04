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
    $response->assertHeader('X-Robots-Tag', 'noindex');
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
    $response->assertHeader('X-Robots-Tag', 'noindex');
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

test('downloading with an ids filter only includes the selected photos', function () {
    $album = Album::factory()->create(['title' => 'Sommerurlaub']);
    $keep1 = Photo::factory()->for($album)->processed()->create();
    $keep2 = Photo::factory()->for($album)->processed()->create();
    $excluded = Photo::factory()->for($album)->processed()->create();

    foreach ([$keep1, $keep2, $excluded] as $photo) {
        File::ensureDirectoryExists($photo->directoryPath());
        File::put($photo->originalPath(), 'fake-image-bytes');
    }

    $response = $this->get(route('albums.download', ['album' => $album, 'ids' => "{$keep1->id},{$keep2->id}"]));

    $response->assertDownload(Str::slug($album->title).'.zip');
});

test('downloading with a single id in the ids filter returns the original file', function () {
    $album = Album::factory()->create();
    $photo = Photo::factory()->for($album)->processed()->create(['title' => 'Strand']);
    Photo::factory()->for($album)->processed()->create();

    File::ensureDirectoryExists($photo->directoryPath());
    File::put($photo->originalPath(), 'fake-image-bytes');

    $response = $this->get(route('albums.download', ['album' => $album, 'ids' => (string) $photo->id]));

    $response->assertDownload('strand.jpg');
});

test('ids belonging to another album are ignored', function () {
    $album = Album::factory()->create();
    $otherAlbum = Album::factory()->create();
    $ownPhoto = Photo::factory()->for($album)->processed()->create(['title' => 'Eigenes']);
    $foreignPhoto = Photo::factory()->for($otherAlbum)->processed()->create();

    foreach ([$ownPhoto, $foreignPhoto] as $photo) {
        File::ensureDirectoryExists($photo->directoryPath());
        File::put($photo->originalPath(), 'fake-image-bytes');
    }

    $response = $this->get(route('albums.download', ['album' => $album, 'ids' => "{$ownPhoto->id},{$foreignPhoto->id}"]));

    $response->assertDownload('eigenes.jpg');
});

test('an ids filter matching nothing returns 404', function () {
    $album = Album::factory()->create();
    Photo::factory()->for($album)->processed()->create();

    $response = $this->get(route('albums.download', ['album' => $album, 'ids' => '999999']));

    $response->assertNotFound();
});
