<?php

use App\Livewire\Admin\Analytics;
use App\Models\Album;
use App\Models\Page;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Http\Request;
use Livewire\Livewire;

test('guests are redirected to login', function () {
    $response = $this->get(route('admin.analytics'));

    $response->assertRedirect(route('login'));
});

test('it shows the total and unique view counts per album', function () {
    $user = User::factory()->create();
    $album = Album::factory()->create(['title' => 'Urlaub']);

    // Same visitor cookie on both requests, so this simulates one returning
    // visitor: two views total, but only one unique visitor.
    app()->instance('request', Request::create('/', 'GET', cookies: ['eloquent_viewable' => 'visitor-abc']));
    views($album)->record();
    views($album)->record();

    Livewire::actingAs($user)
        ->test(Analytics::class)
        ->assertSeeText('Urlaub')
        ->assertSeeInOrder(['Urlaub', '2', '1']);
});

test('it shows the total and unique view counts per photo', function () {
    $user = User::factory()->create();
    $album = Album::factory()->create();
    $photo = Photo::factory()->for($album)->create(['title' => 'Sonnenuntergang']);

    views($photo)->record();

    Livewire::actingAs($user)
        ->test(Analytics::class)
        ->assertSeeText('Sonnenuntergang')
        ->assertSeeInOrder(['Sonnenuntergang', $album->title, '1', '1']);
});

test('it shows the total and unique view counts per page', function () {
    $user = User::factory()->create();
    $page = Page::factory()->create(['title' => 'Über mich']);

    app()->instance('request', Request::create('/', 'GET', cookies: ['eloquent_viewable' => 'visitor-abc']));
    views($page)->record();
    views($page)->record();

    Livewire::actingAs($user)
        ->test(Analytics::class)
        ->assertSeeText('Über mich')
        ->assertSeeInOrder(['Über mich', '2', '1']);
});

test('the homepage is excluded from the pages table since it is already shown separately', function () {
    $user = User::factory()->create();
    $homepage = Page::forSlug('welcome');
    views($homepage)->record();

    $component = Livewire::actingAs($user)->test(Analytics::class);

    expect($component->get('pages')->pluck('id'))->not->toContain($homepage->id);
});

test('opening the history modal for an album shows its view history and origins', function () {
    $user = User::factory()->create();
    $album = Album::factory()->create(['title' => 'Urlaub']);

    app()->instance('request', Request::create('/', 'GET', server: ['HTTP_REFERER' => 'https://www.google.com/']));
    views($album)->record();

    Livewire::actingAs($user)
        ->test(Analytics::class)
        ->call('showHistory', 'album', $album->id)
        ->assertSet('showHistoryModal', true)
        ->assertSeeText('Urlaub')
        ->assertSeeText('Letzte 7 Tage')
        ->assertSeeText('Insgesamt: 1 Aufrufe')
        ->assertSeeText('www.google.com');
});

test('opening the history modal for a photo resolves its title', function () {
    $user = User::factory()->create();
    $album = Album::factory()->create();
    $photo = Photo::factory()->for($album)->create(['title' => 'Sonnenuntergang']);

    Livewire::actingAs($user)
        ->test(Analytics::class)
        ->call('showHistory', 'photo', $photo->id)
        ->assertSeeText('Verlauf: Sonnenuntergang');
});

test('opening the history modal for a page resolves its title', function () {
    $user = User::factory()->create();
    $page = Page::factory()->create(['title' => 'Über mich']);

    Livewire::actingAs($user)
        ->test(Analytics::class)
        ->call('showHistory', 'page', $page->id)
        ->assertSeeText('Verlauf: Über mich');
});

test('the homepage card has a history button labelled "Startseite"', function () {
    $user = User::factory()->create();
    $homepage = Page::forSlug('welcome');

    Livewire::actingAs($user)
        ->test(Analytics::class)
        ->call('showHistory', 'page', $homepage->id)
        ->assertSeeText('Verlauf: Startseite');
});
