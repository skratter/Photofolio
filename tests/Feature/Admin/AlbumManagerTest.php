<?php

use App\Livewire\Admin\AlbumManager;
use App\Models\Album;
use App\Models\Photo;
use App\Models\User;
use Livewire\Livewire;

test('the album list shows each album\'s photo count', function () {
    $user = User::factory()->create();
    $album = Album::factory()->create();
    Photo::factory()->for($album)->count(3)->create();

    Livewire::actingAs($user)
        ->test(AlbumManager::class)
        ->assertSeeText('3');
});

test('marking an album as the homepage unmarks the previous one', function () {
    $user = User::factory()->create();
    $current = Album::factory()->create(['is_homepage' => true]);
    $other = Album::factory()->create();

    Livewire::actingAs($user)
        ->test(AlbumManager::class)
        ->call('openEditModal', $other->id)
        ->set('form.is_homepage', true)
        ->call('save');

    expect($current->fresh()->is_homepage)->toBeFalse()
        ->and($other->fresh()->is_homepage)->toBeTrue();
});

test('a private album cannot be saved as the homepage', function () {
    $user = User::factory()->create();
    $album = Album::factory()->create();

    Livewire::actingAs($user)
        ->test(AlbumManager::class)
        ->call('openEditModal', $album->id)
        ->set('form.visibility', 'private')
        ->set('form.password', 'secret123')
        ->set('form.is_homepage', true)
        ->call('save');

    expect($album->fresh()->is_homepage)->toBeFalse();
});
