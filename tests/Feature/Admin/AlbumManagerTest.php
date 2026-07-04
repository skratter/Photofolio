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
