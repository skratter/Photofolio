<?php

use App\Actions\BuildViewOriginsAction;
use App\Models\Photo;
use CyrildeWit\EloquentViewable\View;

test('it counts referrers by host and falls back to "Direkt aufgerufen" for empty ones', function () {
    $photo = Photo::factory()->create();

    View::create(['viewable_id' => $photo->id, 'viewable_type' => $photo->getMorphClass(), 'referrer' => 'https://www.google.com/search?q=foo']);
    View::create(['viewable_id' => $photo->id, 'viewable_type' => $photo->getMorphClass(), 'referrer' => 'https://www.google.com/search?q=bar']);
    View::create(['viewable_id' => $photo->id, 'viewable_type' => $photo->getMorphClass(), 'referrer' => null]);

    $origins = (new BuildViewOriginsAction)->execute($photo);

    expect($origins['referrers']->firstWhere('label', 'www.google.com')['count'])->toBe(2)
        ->and($origins['referrers']->firstWhere('label', 'Direkt aufgerufen')['count'])->toBe(1);
});

test('it counts user agents verbatim and falls back to "Unbekannt" for empty ones', function () {
    $photo = Photo::factory()->create();

    View::create(['viewable_id' => $photo->id, 'viewable_type' => $photo->getMorphClass(), 'user_agent' => 'curl/8.0']);
    View::create(['viewable_id' => $photo->id, 'viewable_type' => $photo->getMorphClass(), 'user_agent' => 'curl/8.0']);
    View::create(['viewable_id' => $photo->id, 'viewable_type' => $photo->getMorphClass(), 'user_agent' => null]);

    $origins = (new BuildViewOriginsAction)->execute($photo);

    expect($origins['userAgents']->firstWhere('label', 'curl/8.0')['count'])->toBe(2)
        ->and($origins['userAgents']->firstWhere('label', 'Unbekannt')['count'])->toBe(1);
});

test('it only counts views belonging to the given viewable', function () {
    $photo = Photo::factory()->create();
    $otherPhoto = Photo::factory()->create();

    View::create(['viewable_id' => $photo->id, 'viewable_type' => $photo->getMorphClass(), 'referrer' => 'https://example.com']);
    View::create(['viewable_id' => $otherPhoto->id, 'viewable_type' => $otherPhoto->getMorphClass(), 'referrer' => 'https://other.example']);

    $origins = (new BuildViewOriginsAction)->execute($photo);

    expect($origins['referrers'])->toHaveCount(1)
        ->and($origins['referrers']->first()['label'])->toBe('example.com');
});

test('it flags referrers that match a configured own domain', function () {
    config(['analytics.own_domains' => ['skratter.com']]);
    $photo = Photo::factory()->create();

    View::create(['viewable_id' => $photo->id, 'viewable_type' => $photo->getMorphClass(), 'referrer' => 'https://skratter.com/']);
    View::create(['viewable_id' => $photo->id, 'viewable_type' => $photo->getMorphClass(), 'referrer' => 'https://skratter.com/']);
    View::create(['viewable_id' => $photo->id, 'viewable_type' => $photo->getMorphClass(), 'referrer' => 'https://example.com']);

    $origins = (new BuildViewOriginsAction)->execute($photo);

    expect($origins['referrers']->firstWhere('label', 'skratter.com')['isOwn'])->toBeTrue()
        ->and($origins['referrers']->firstWhere('label', 'example.com')['isOwn'])->toBeFalse()
        ->and($origins['ownDomainsTotal'])->toBe(2)
        ->and($origins['total'])->toBe(3);
});
