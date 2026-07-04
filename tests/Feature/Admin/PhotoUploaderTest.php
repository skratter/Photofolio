<?php

use App\Jobs\ProcessUploadedPhoto;
use App\Livewire\Admin\PhotoUploader;
use App\Models\Album;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    // Fake both disks so tests never touch real storage: "photos" (Photo IDs
    // are reused across test runs via RefreshDatabase and would otherwise
    // collide with real uploaded files) and "local" (Livewire's default temp
    // upload disk).
    Storage::fake('photos');
    Storage::fake('local');
});

test('uploading a photo creates a record, moves the file, and dispatches processing', function () {
    Queue::fake();

    $user = User::factory()->create();
    $album = Album::factory()->create();

    Livewire::actingAs($user)
        ->test(PhotoUploader::class, ['album' => $album])
        ->set('photos', [UploadedFile::fake()->image('holiday.jpg')])
        ->assertHasNoErrors();

    $photo = Photo::where('album_id', $album->id)->sole();

    expect($photo->original_filename)->toBe('holiday.jpg')
        ->and($photo->disk_path)->toBe("photos/{$photo->id}/original.jpg")
        ->and(File::exists($photo->originalPath()))->toBeTrue();

    Queue::assertPushed(ProcessUploadedPhoto::class);

    // storeAs() copies across disks rather than moving - the temp upload must
    // be deleted explicitly, otherwise it lingers in livewire-tmp indefinitely.
    expect(Storage::disk('local')->allFiles('livewire-tmp'))->toBeEmpty();
});

test('rejects non-image uploads', function () {
    $user = User::factory()->create();
    $album = Album::factory()->create();

    Livewire::actingAs($user)
        ->test(PhotoUploader::class, ['album' => $album])
        ->set('photos', [UploadedFile::fake()->create('document.pdf', 10)])
        ->assertHasErrors(['photos.0']);

    expect(Photo::where('album_id', $album->id)->count())->toBe(0);
});
