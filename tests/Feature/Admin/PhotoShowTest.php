<?php

use App\Livewire\Admin\PhotoShow;
use App\Models\Album;
use App\Models\Photo;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('photos');
});

test('guests are redirected to login', function () {
    $album = Album::factory()->create();
    $photo = Photo::factory()->for($album)->processed()->create();

    $response = $this->get(route('admin.albums.photos.show', [$album, $photo]));

    $response->assertRedirect(route('login'));
});

test('returns 404 when the photo does not belong to the album', function () {
    $user = User::factory()->create();
    $album = Album::factory()->create();
    $otherAlbum = Album::factory()->create();
    $photo = Photo::factory()->for($otherAlbum)->processed()->create();

    $response = $this->actingAs($user)->get(route('admin.albums.photos.show', [$album, $photo]));

    $response->assertNotFound();
});

test('authenticated users can view a photo\'s details', function () {
    $user = User::factory()->create();
    $album = Album::factory()->create();
    $photo = Photo::factory()->for($album)->processed()->create([
        'title' => 'Sonnenuntergang',
        'camera_make' => 'Fujifilm',
        'camera_model' => 'X-T5',
        'width' => 1920,
        'height' => 1080,
    ]);

    $response = $this->actingAs($user)->get(route('admin.albums.photos.show', [$album, $photo]));

    $response->assertOk();
    $response->assertSee('Sonnenuntergang');
    $response->assertSee('Fujifilm');
    $response->assertSee('1920 × 1080 px');
});

test('updating title, notes, and tags persists the changes', function () {
    $user = User::factory()->create();
    $album = Album::factory()->create();
    $photo = Photo::factory()->for($album)->processed()->create();
    Tag::factory()->create(['name' => 'Strand', 'slug' => 'strand']);

    Livewire::actingAs($user)
        ->test(PhotoShow::class, ['album' => $album, 'photo' => $photo])
        ->set('form.title', 'Am Strand')
        ->set('form.notes', 'Aufgenommen bei Sonnenuntergang')
        ->set('form.tags', 'Strand, Sonnenuntergang')
        ->call('save')
        ->assertHasNoErrors();

    $photo->refresh();

    expect($photo->title)->toBe('Am Strand')
        ->and($photo->notes)->toBe('Aufgenommen bei Sonnenuntergang')
        ->and($photo->tags->pluck('name')->sort()->values()->all())->toBe(['Sonnenuntergang', 'Strand']);

    expect(Tag::where('slug', 'strand')->count())->toBe(1);
});

test('title is required to be a string but may be cleared', function () {
    $user = User::factory()->create();
    $album = Album::factory()->create();
    $photo = Photo::factory()->for($album)->processed()->create(['title' => 'Alter Titel']);

    Livewire::actingAs($user)
        ->test(PhotoShow::class, ['album' => $album, 'photo' => $photo])
        ->set('form.title', '')
        ->call('save')
        ->assertHasNoErrors();

    expect($photo->fresh()->title)->toBeNull();
});
