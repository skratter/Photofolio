<?php

use App\Models\Page;
use App\Models\User;

test('it renders the welcome page', function () {
    $response = $this->get('/');

    $response->assertOk();
});

test('it records a view for a guest visitor', function () {
    $this->get('/');

    expect(views(Page::forSlug('welcome'))->count())->toBe(1);
});

test('it does not record a view for an authenticated user', function () {
    $this->actingAs(User::factory()->create())->get('/');

    expect(views(Page::forSlug('welcome'))->count())->toBe(0);
});
