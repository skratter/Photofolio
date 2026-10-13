<?php

use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route;

test('the chord finder page renders when the feature is enabled', function () {
    $this->get(route('chord-finder'))
        ->assertOk()
        ->assertSeeText('Akkordfinder');
});

test('the chord finder offers several sounds', function () {
    $this->get(route('chord-finder'))
        ->assertSeeText('Klang: Klavier')
        ->assertSeeText('Klang: Synthesizer');
});

test('the navigation links to the chord finder under "Sonstiges"', function () {
    $this->get(route('home'))
        ->assertSeeText('Sonstiges')
        ->assertSee(route('chord-finder'));
});

test('the sitemap lists the chord finder', function () {
    $this->get(route('sitemap'))->assertSee(route('chord-finder'));
});

test('the chord finder is not registered when the feature is disabled', function () {
    config(['app.features.chord_finder' => false]);

    $router = app('router');
    $router->setRoutes(new RouteCollection);

    require base_path('routes/web.php');

    $router->getRoutes()->refreshNameLookups();

    expect(Route::has('chord-finder'))->toBeFalse();
});
