<?php

use App\Models\Album;

test('it lists public albums', function () {
    Album::factory()->create(['title' => 'Urlaub 2026']);

    $response = $this->get(route('albums.index'));

    $response->assertOk()->assertSeeText('Urlaub 2026');
});

test('it does not list private albums', function () {
    Album::factory()->private()->create(['title' => 'Geheimes Album']);

    $response = $this->get(route('albums.index'));

    $response->assertOk()->assertDontSeeText('Geheimes Album');
});
