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
