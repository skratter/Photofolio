<?php

use App\Livewire\Admin\AlbumShow;
use App\Models\Album;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;

beforeEach(function () {
    // Fake the "photos" disk so tests never touch the real storage/app/photos
    // directory - Photo IDs are reused across test runs (RefreshDatabase resets
    // auto-increment) and would otherwise collide with real uploaded files.
    Storage::fake('photos');
});

test('guests are redirected to login', function () {
    $album = Album::factory()->create();

    $response = $this->get(route('admin.albums.show', $album));

    $response->assertRedirect(route('login'));
});

test('authenticated users can view an album and its photos', function () {
    $user = User::factory()->create();
    $album = Album::factory()->create(['title' => 'Sommerurlaub']);
    Photo::factory()->for($album)->processed()->count(2)->create();

    $response = $this->actingAs($user)->get(route('admin.albums.show', $album));

    $response->assertOk();
    $response->assertSee('Sommerurlaub');
});

test('setting a cover photo updates the album', function () {
    $user = User::factory()->create();
    $album = Album::factory()->create();
    $photo = Photo::factory()->for($album)->processed()->create();

    Livewire::actingAs($user)
        ->test(AlbumShow::class, ['album' => $album])
        ->call('setCoverPhoto', $photo->id);

    expect($album->fresh()->cover_photo_id)->toBe($photo->id);
});

test('setting a cover photo rejects a photo from another album', function () {
    $user = User::factory()->create();
    $album = Album::factory()->create();
    $otherAlbumsPhoto = Photo::factory()->for(Album::factory())->processed()->create();

    expect(fn () => Livewire::actingAs($user)
        ->test(AlbumShow::class, ['album' => $album])
        ->call('setCoverPhoto', $otherAlbumsPhoto->id)
    )->toThrow(ModelNotFoundException::class);
});

test('deleting the cover photo clears it from the album', function () {
    $user = User::factory()->create();
    $album = Album::factory()->create();
    $photo = Photo::factory()->for($album)->processed()->create();
    $album->update(['cover_photo_id' => $photo->id]);

    Livewire::actingAs($user)
        ->test(AlbumShow::class, ['album' => $album])
        ->call('deletePhoto', $photo->id);

    expect($album->fresh()->cover_photo_id)->toBeNull();
});

test('deleting a photo removes the database row and its storage directory', function () {
    $user = User::factory()->create();
    $album = Album::factory()->create();
    $photo = Photo::factory()->for($album)->processed()->create();

    File::ensureDirectoryExists($photo->directoryPath());
    File::put($photo->thumbPath(), 'fake-thumb-contents');

    Livewire::actingAs($user)
        ->test(AlbumShow::class, ['album' => $album])
        ->call('deletePhoto', $photo->id);

    $this->assertModelMissing($photo);
    expect(File::isDirectory($photo->directoryPath()))->toBeFalse();
});

test('bulk deleting removes the selected photos and leaves others untouched', function () {
    $user = User::factory()->create();
    $album = Album::factory()->create();
    $keep = Photo::factory()->for($album)->processed()->create();
    $delete1 = Photo::factory()->for($album)->processed()->create();
    $delete2 = Photo::factory()->for($album)->processed()->create();

    Livewire::actingAs($user)
        ->test(AlbumShow::class, ['album' => $album])
        ->set('selectedPhotoIds', [$delete1->id, $delete2->id])
        ->call('bulkDelete')
        ->assertSet('selectedPhotoIds', []);

    $this->assertModelMissing($delete1);
    $this->assertModelMissing($delete2);
    $this->assertModelExists($keep);
});

test('bulk renaming applies a base name with sequential numbering in sort order', function () {
    $user = User::factory()->create();
    $album = Album::factory()->create();
    $first = Photo::factory()->for($album)->processed()->create(['sort_order' => 0]);
    $second = Photo::factory()->for($album)->processed()->create(['sort_order' => 1]);
    $untouched = Photo::factory()->for($album)->processed()->create(['sort_order' => 2]);

    Livewire::actingAs($user)
        ->test(AlbumShow::class, ['album' => $album])
        ->set('selectedPhotoIds', [$first->id, $second->id])
        ->set('bulkRenameBaseName', 'Urlaub')
        ->call('bulkRename')
        ->assertSet('showBulkRenameModal', false)
        ->assertSet('selectedPhotoIds', []);

    expect($first->fresh()->title)->toBe('Urlaub 1')
        ->and($second->fresh()->title)->toBe('Urlaub 2')
        ->and($untouched->fresh()->title)->toBeNull();
});

test('bulk renaming requires a base name', function () {
    $user = User::factory()->create();
    $album = Album::factory()->create();
    $photo = Photo::factory()->for($album)->processed()->create();

    Livewire::actingAs($user)
        ->test(AlbumShow::class, ['album' => $album])
        ->set('selectedPhotoIds', [$photo->id])
        ->set('bulkRenameBaseName', '')
        ->call('bulkRename')
        ->assertHasErrors(['bulkRenameBaseName' => 'required']);

    expect($photo->fresh()->title)->toBeNull();
});

test('downloading a single selected photo returns the original file', function () {
    $user = User::factory()->create();
    $album = Album::factory()->create();
    $photo = Photo::factory()->for($album)->processed()->create(['title' => 'Strand']);

    File::ensureDirectoryExists($photo->directoryPath());
    File::put($photo->originalPath(), 'fake-image-bytes');

    Livewire::actingAs($user)
        ->test(AlbumShow::class, ['album' => $album])
        ->set('selectedPhotoIds', [$photo->id])
        ->call('download')
        ->assertFileDownloaded('strand.jpg');
});

test('downloading multiple selected photos returns a zip', function () {
    $user = User::factory()->create();
    $album = Album::factory()->create(['title' => 'Sommerurlaub']);
    $photo1 = Photo::factory()->for($album)->processed()->create();
    $photo2 = Photo::factory()->for($album)->processed()->create();

    foreach ([$photo1, $photo2] as $photo) {
        File::ensureDirectoryExists($photo->directoryPath());
        File::put($photo->originalPath(), 'fake-image-bytes');
    }

    Livewire::actingAs($user)
        ->test(AlbumShow::class, ['album' => $album])
        ->set('selectedPhotoIds', [$photo1->id, $photo2->id])
        ->call('download')
        ->assertFileDownloaded(Str::slug($album->title).'.zip');
});
