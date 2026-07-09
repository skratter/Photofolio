<?php

use App\Livewire\Admin\Dashboard;
use App\Models\Album;
use App\Models\Photo;
use App\Models\Setting;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected to login', function () {
    $response = $this->get(route('admin.dashboard'));

    $response->assertRedirect(route('login'));
});

test('the sidebar brand reflects the configured site name', function () {
    $user = User::factory()->create();
    Setting::current()->update(['site_name' => 'Meine Fotoseite']);

    $response = $this->actingAs($user)->get(route('admin.dashboard'));

    $response->assertOk()->assertSeeText('Meine Fotoseite');
});

test('it shows the album count, photo count and top album', function () {
    $user = User::factory()->create();
    $topAlbum = Album::factory()->create(['title' => 'Urlaub']);
    $otherAlbum = Album::factory()->create(['title' => 'Alltag']);
    Photo::factory()->for($topAlbum)->count(2)->create();

    views($topAlbum)->record();
    views($topAlbum)->record();
    views($otherAlbum)->record();

    Livewire::actingAs($user)
        ->test(Dashboard::class)
        ->assertSeeText('2') // Alben
        ->assertSeeText('Urlaub')
        ->assertSet('albumsCount', 2)
        ->assertSet('photosCount', 2);
});

test('it shows the top photo', function () {
    $user = User::factory()->create();
    $album = Album::factory()->create();
    $topPhoto = Photo::factory()->for($album)->create(['title' => 'Sonnenuntergang']);
    $otherPhoto = Photo::factory()->for($album)->create(['title' => 'Berge']);

    views($topPhoto)->record();
    views($topPhoto)->record();
    views($otherPhoto)->record();

    Livewire::actingAs($user)
        ->test(Dashboard::class)
        ->assertSeeText('Sonnenuntergang');
});
