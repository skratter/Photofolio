<?php

use App\Models\Album;
use App\Models\Photo;

test('meta description returns the trimmed, truncated notes', function () {
    $photo = Photo::factory()->for(Album::factory())->create(['notes' => '  Ein schöner Sonnenuntergang.  ']);

    expect($photo->metaDescription())->toBe('Ein schöner Sonnenuntergang.');
});

test('meta description is null when there are no notes', function () {
    $photo = Photo::factory()->for(Album::factory())->create(['notes' => null]);

    expect($photo->metaDescription())->toBeNull();
});

test('meta description is truncated to 160 characters plus an ellipsis', function () {
    $photo = Photo::factory()->for(Album::factory())->create(['notes' => str_repeat('a', 200)]);

    expect($photo->metaDescription())->toBe(str_repeat('a', 160).'...');
});
