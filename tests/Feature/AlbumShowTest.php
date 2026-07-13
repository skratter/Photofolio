<?php

use App\Livewire\AlbumShow;
use App\Models\Album;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('photos');
});

test('a public album shows its photos directly', function () {
    $album = Album::factory()->create(['title' => 'Sommerurlaub']);
    Photo::factory()->for($album)->processed()->create();

    $response = $this->get(route('albums.show', $album->slug));

    $response->assertOk()
        ->assertSeeText('Sommerurlaub')
        ->assertDontSeeText('passwortgeschützt');
});

test('the description is rendered as raw, unescaped html', function () {
    $album = Album::factory()->create([
        'description' => '<p>Ein Wochenende in <a href="https://example.com">Beispielstadt</a>.</p>',
    ]);

    $response = $this->get(route('albums.show', $album->slug));

    $response->assertOk()
        ->assertSee('<p>Ein Wochenende in <a href="https://example.com">Beispielstadt</a>.</p>', false);
});

test('a public album shows meta description and open graph tags derived from its description', function () {
    $album = Album::factory()->create([
        'title' => 'Sommerurlaub',
        'description' => '<p>Ein Wochenende in <strong>Beispielstadt</strong>.</p>',
    ]);
    $photo = Photo::factory()->for($album)->processed()->create();

    $response = $this->get(route('albums.show', $album->slug));

    $response->assertOk()
        ->assertSee('<meta name="description" content="Ein Wochenende in Beispielstadt.">', false)
        ->assertSee('<meta property="og:title" content="Sommerurlaub">', false)
        ->assertSee(
            '<meta property="og:image" content="'.route('albums.photos.display', [$album, $photo]).'">',
            false
        );
});

test('a locked private album does not leak meta description or open graph tags', function () {
    $album = Album::factory()->private()->create([
        'title' => 'Geheimalbum',
        'description' => '<p>Streng geheim.</p>',
    ]);
    $album->setPassword('secret123');
    $album->save();
    Photo::factory()->for($album)->processed()->create();

    $response = $this->get(route('albums.show', $album->slug));

    $response->assertOk()
        ->assertDontSee('name="description"', false)
        ->assertDontSee('og:title', false)
        ->assertDontSee('og:image', false);
});

test('a private album shows a password form instead of its photos', function () {
    $album = Album::factory()->private()->create(['title' => 'Geheimes Album']);
    $album->setPassword('secret123');
    $album->save();
    Photo::factory()->for($album)->processed()->create(['title' => 'Geheimfoto']);

    $response = $this->get(route('albums.show', $album->slug));

    $response->assertOk()
        ->assertSeeText('passwortgeschützt')
        ->assertDontSeeText('Geheimfoto');
});

test('an incorrect password shows an error and does not unlock the album', function () {
    $album = Album::factory()->private()->create();
    $album->setPassword('secret123');
    $album->save();

    Livewire::test(AlbumShow::class, ['album' => $album])
        ->set('password', 'wrong')
        ->call('unlock')
        ->assertHasErrors(['password']);

    expect(session()->get($album->unlockSessionKey()))->toBeNull();
});

test('the correct password unlocks the album and reveals its photos', function () {
    $album = Album::factory()->private()->create();
    $album->setPassword('secret123');
    $album->save();
    Photo::factory()->for($album)->processed()->create();

    Livewire::test(AlbumShow::class, ['album' => $album])
        ->set('password', 'secret123')
        ->call('unlock')
        ->assertHasNoErrors();

    expect(session()->get($album->unlockSessionKey()))->toBeTrue();
});

test('it records a view for a public album', function () {
    $album = Album::factory()->create();

    $this->get(route('albums.show', $album->slug));

    expect(views($album)->count())->toBe(1);
});

test('it does not record a view for a locked private album', function () {
    $album = Album::factory()->private()->create();
    $album->setPassword('secret123');
    $album->save();

    $this->get(route('albums.show', $album->slug));

    expect(views($album)->count())->toBe(0);
});

test('it records a view once a private album is unlocked', function () {
    $album = Album::factory()->private()->create();
    $album->setPassword('secret123');
    $album->save();

    Livewire::test(AlbumShow::class, ['album' => $album])
        ->set('password', 'secret123')
        ->call('unlock');

    expect(views($album)->count())->toBe(1);
});

test('it does not record a view for an authenticated user', function () {
    $album = Album::factory()->create();

    $this->actingAs(User::factory()->create())->get(route('albums.show', $album->slug));

    expect(views($album)->count())->toBe(0);
});

test('it shows only the first page of photos and pagination controls for larger albums', function () {
    $album = Album::factory()->create();
    Photo::factory()->for($album)->processed()->count(45)->create();

    $component = Livewire::test(AlbumShow::class, ['album' => $album])
        ->assertSeeHtml('wire:click="goToPage(2)"');

    expect($component->instance()->photos)->toHaveCount(30);
});

test('an album that fits on one page shows no pagination controls', function () {
    $album = Album::factory()->create();
    Photo::factory()->for($album)->processed()->count(10)->create();

    Livewire::test(AlbumShow::class, ['album' => $album])
        ->assertDontSeeHtml('wire:click="goToPage(2)"');
});

test('jumping to a page shows that page\'s photos', function () {
    $album = Album::factory()->create();
    Photo::factory()->for($album)->processed()->count(45)->create();

    $component = Livewire::test(AlbumShow::class, ['album' => $album])
        ->call('goToPage', 2);

    expect($component->instance()->photos)->toHaveCount(15);
});

test('jumping to an out-of-range page clamps to the nearest valid page', function () {
    $album = Album::factory()->create();
    Photo::factory()->for($album)->processed()->count(45)->create();

    $component = Livewire::test(AlbumShow::class, ['album' => $album])
        ->call('goToPage', 99)
        ->assertSet('page', 2);

    expect($component->instance()->photos)->toHaveCount(15);

    $component->call('goToPage', 0)->assertSet('page', 1);
});

test('a public album with photos shows a download button that starts selection mode', function () {
    $album = Album::factory()->create();
    Photo::factory()->for($album)->processed()->create();

    Livewire::test(AlbumShow::class, ['album' => $album])
        ->assertSeeHtml('wire:click="startSelecting"');
});

test('an album with downloads disabled does not show a download button', function () {
    $album = Album::factory()->create(['downloads_enabled' => false]);
    Photo::factory()->for($album)->processed()->create();

    Livewire::test(AlbumShow::class, ['album' => $album])
        ->assertDontSeeHtml('wire:click="startSelecting"');
});

test('a locked private album does not show a download button', function () {
    $album = Album::factory()->private()->create();
    $album->setPassword('secret123');
    $album->save();
    Photo::factory()->for($album)->processed()->create();

    Livewire::test(AlbumShow::class, ['album' => $album])
        ->assertDontSeeHtml('wire:click="startSelecting"');
});

test('starting selection mode shows the selection toolbar and the full-album download link', function () {
    $album = Album::factory()->create();
    Photo::factory()->for($album)->processed()->create();

    Livewire::test(AlbumShow::class, ['album' => $album])
        ->call('startSelecting')
        ->assertSet('selecting', true)
        ->assertSeeText('0 ausgewählt')
        ->assertSeeHtml(route('albums.download', $album));
});

test('toggling a photo adds and then removes it from the selection', function () {
    $album = Album::factory()->create();
    $photo = Photo::factory()->for($album)->processed()->create();

    $component = Livewire::test(AlbumShow::class, ['album' => $album])
        ->call('startSelecting')
        ->call('toggleSelect', $photo->id)
        ->assertSet('selectedPhotoIds', [$photo->id]);

    $component->call('toggleSelect', $photo->id)
        ->assertSet('selectedPhotoIds', []);
});

test('selection survives switching pages', function () {
    $album = Album::factory()->create();
    $photos = Photo::factory()->for($album)->processed()->count(45)->create();

    Livewire::test(AlbumShow::class, ['album' => $album])
        ->call('startSelecting')
        ->call('toggleSelect', $photos->first()->id)
        ->call('goToPage', 2)
        ->assertSet('selectedPhotoIds', [$photos->first()->id])
        ->assertSet('selecting', true);
});

test('selecting all on the current page adds to an existing cross-page selection', function () {
    $album = Album::factory()->create();
    $photos = Photo::factory()->for($album)->processed()->count(45)->create();
    $firstPageIds = $photos->take(30)->pluck('id')->all();
    $fromPageTwo = $photos->last()->id;

    $component = Livewire::test(AlbumShow::class, ['album' => $album])
        ->call('startSelecting')
        ->call('goToPage', 2)
        ->call('toggleSelect', $fromPageTwo)
        ->call('goToPage', 1)
        ->call('selectAllOnPage');

    expect($component->get('selectedPhotoIds'))
        ->toEqualCanonicalizing([...$firstPageIds, $fromPageTwo]);
});

test('clearing the selection empties it without leaving selection mode', function () {
    $album = Album::factory()->create();
    $photo = Photo::factory()->for($album)->processed()->create();

    Livewire::test(AlbumShow::class, ['album' => $album])
        ->call('startSelecting')
        ->call('toggleSelect', $photo->id)
        ->call('clearSelection')
        ->assertSet('selectedPhotoIds', [])
        ->assertSet('selecting', true);
});

test('stopping selection clears the selection and leaves selection mode', function () {
    $album = Album::factory()->create();
    $photo = Photo::factory()->for($album)->processed()->create();

    Livewire::test(AlbumShow::class, ['album' => $album])
        ->call('startSelecting')
        ->call('toggleSelect', $photo->id)
        ->call('stopSelecting')
        ->assertSet('selecting', false)
        ->assertSet('selectedPhotoIds', []);
});

test('a selected photo shows a download link scoped to that selection', function () {
    $album = Album::factory()->create();
    $photo = Photo::factory()->for($album)->processed()->create();

    Livewire::test(AlbumShow::class, ['album' => $album])
        ->call('startSelecting')
        ->call('toggleSelect', $photo->id)
        ->assertSeeHtml(route('albums.download', ['album' => $album, 'ids' => (string) $photo->id]));
});

test('a private album shows a download button once unlocked', function () {
    $album = Album::factory()->private()->create();
    $album->setPassword('secret123');
    $album->save();
    Photo::factory()->for($album)->processed()->create();

    Livewire::test(AlbumShow::class, ['album' => $album])
        ->set('password', 'secret123')
        ->call('unlock')
        ->assertSeeHtml('wire:click="startSelecting"');
});

test('a private album shows the full-album download link once unlocked and selecting', function () {
    $album = Album::factory()->private()->create();
    $album->setPassword('secret123');
    $album->save();
    Photo::factory()->for($album)->processed()->create();

    Livewire::test(AlbumShow::class, ['album' => $album])
        ->set('password', 'secret123')
        ->call('unlock')
        ->call('startSelecting')
        ->assertSeeHtml(route('albums.download', $album));
});
