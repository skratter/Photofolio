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
