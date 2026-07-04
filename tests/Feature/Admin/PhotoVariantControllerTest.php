<?php

use App\Models\Photo;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    // Fake the "photos" disk so tests never touch the real storage/app/photos
    // directory - Photo IDs are reused across test runs (RefreshDatabase resets
    // auto-increment) and would otherwise collide with real uploaded files.
    Storage::fake('photos');
});

test('guests are redirected to login', function () {
    $photo = Photo::factory()->processed()->create();

    $response = $this->get(route('admin.photos.thumb', $photo));

    $response->assertRedirect(route('login'));
});

test('serves the thumb variant of a processed photo', function () {
    $user = User::factory()->create();
    $photo = Photo::factory()->processed()->create();

    File::ensureDirectoryExists($photo->directoryPath());
    File::put($photo->thumbPath(), 'fake-thumb-contents');

    $response = $this->actingAs($user)->get(route('admin.photos.thumb', $photo));

    $response->assertOk();
});

test('returns 404 for an unprocessed photo', function () {
    $user = User::factory()->create();
    $photo = Photo::factory()->create(['processed_at' => null]);

    $response = $this->actingAs($user)->get(route('admin.photos.thumb', $photo));

    $response->assertNotFound();
});
