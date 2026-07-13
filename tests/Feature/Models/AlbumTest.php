<?php

use App\Models\Album;
use App\Models\Photo;

test('effective cover photo prefers the explicitly set cover photo', function () {
    $album = Album::factory()->create();
    $first = Photo::factory()->for($album)->create(['sort_order' => 0]);
    $cover = Photo::factory()->for($album)->create(['sort_order' => 1]);
    $album->update(['cover_photo_id' => $cover->id]);

    expect($album->fresh()->effectiveCoverPhoto()->id)->toBe($cover->id);
});

test('effective cover photo falls back to the first photo by sort order', function () {
    $album = Album::factory()->create();
    $second = Photo::factory()->for($album)->create(['sort_order' => 1]);
    $first = Photo::factory()->for($album)->create(['sort_order' => 0]);

    expect($album->effectiveCoverPhoto()->id)->toBe($first->id);
});

test('effective cover photo is null when the album has no photos', function () {
    $album = Album::factory()->create();

    expect($album->effectiveCoverPhoto())->toBeNull();
});

test('a public album is always accessible', function () {
    $album = Album::factory()->create();

    expect($album->isAccessible())->toBeTrue();
});

test('a private album is not accessible without an unlocked session', function () {
    $album = Album::factory()->private()->create();

    expect($album->isAccessible())->toBeFalse();
});

test('a private album is accessible once unlocked in session', function () {
    $album = Album::factory()->private()->create();
    session()->put($album->unlockSessionKey(), true);

    expect($album->isAccessible())->toBeTrue();
});

test('meta description strips html tags from the description', function () {
    $album = Album::factory()->create([
        'description' => '<p>Ein Wochenende in <strong>Beispielstadt</strong>.</p>',
    ]);

    expect($album->metaDescription())->toBe('Ein Wochenende in Beispielstadt.');
});

test('meta description is null when there is no description', function () {
    $album = Album::factory()->create(['description' => null]);

    expect($album->metaDescription())->toBeNull();
});

test('meta description is null when the description only contains markup with no text', function () {
    $album = Album::factory()->create(['description' => '<p><br></p>']);

    expect($album->metaDescription())->toBeNull();
});

test('meta description is truncated to 160 characters plus an ellipsis', function () {
    $album = Album::factory()->create(['description' => '<p>'.str_repeat('a', 200).'</p>']);

    expect($album->metaDescription())->toBe(str_repeat('a', 160).'...');
});
