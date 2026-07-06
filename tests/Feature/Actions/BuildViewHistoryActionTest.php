<?php

use App\Actions\BuildViewHistoryAction;
use App\Models\Photo;
use CyrildeWit\EloquentViewable\View;
use Illuminate\Support\Carbon;

test('it buckets views into daily, monthly, yearly and total', function () {
    Carbon::setTestNow(Carbon::create(2026, 7, 6, 12, 0, 0));
    $photo = Photo::factory()->create();

    foreach ([Carbon::now(), Carbon::now()->subDays(3), Carbon::now()->subDays(10), Carbon::create(2025, 3, 1)] as $viewedAt) {
        View::create(['viewable_id' => $photo->id, 'viewable_type' => $photo->getMorphClass(), 'viewed_at' => $viewedAt]);
    }

    $history = (new BuildViewHistoryAction)->execute($photo);

    expect($history['daily'])->toHaveCount(7)
        ->and($history['daily']->last())->toBe(['label' => '06.07.2026', 'count' => 1])
        ->and($history['monthly']->firstWhere('label', 'Juni 2026')['count'])->toBe(1)
        ->and($history['yearly']->firstWhere('label', '2025')['count'])->toBe(1)
        ->and($history['total'])->toBe(4);

    Carbon::setTestNow();
});

test('daily bucket covers exactly the last 7 days including today', function () {
    Carbon::setTestNow(Carbon::create(2026, 7, 6, 12, 0, 0));
    $photo = Photo::factory()->create();

    foreach ([Carbon::now()->subDays(6)->startOfDay(), Carbon::now()->subDays(7)] as $viewedAt) {
        View::create(['viewable_id' => $photo->id, 'viewable_type' => $photo->getMorphClass(), 'viewed_at' => $viewedAt]);
    }

    $history = (new BuildViewHistoryAction)->execute($photo);

    expect($history['daily']->first())->toBe(['label' => '30.06.2026', 'count' => 1])
        ->and($history['daily']->sum('count'))->toBe(1)
        ->and($history['total'])->toBe(2);

    Carbon::setTestNow();
});

test('it returns zero counts for a viewable with no views', function () {
    $photo = Photo::factory()->create();

    $history = (new BuildViewHistoryAction)->execute($photo);

    expect($history['daily']->sum('count'))->toBe(0)
        ->and($history['monthly'])->toBeEmpty()
        ->and($history['yearly'])->toBeEmpty()
        ->and($history['total'])->toBe(0);
});
