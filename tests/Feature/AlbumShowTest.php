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
