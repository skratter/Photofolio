<?php

use App\Models\Photo;
use CyrildeWit\EloquentViewable\View;
use Illuminate\Http\Request;

test('it captures the referrer and user agent when a view is recorded', function () {
    app()->instance('request', Request::create('/', 'GET', server: [
        'HTTP_REFERER' => 'https://www.google.com/search?q=foo',
        'HTTP_USER_AGENT' => 'Mozilla/5.0 Test Browser',
    ]));

    $photo = Photo::factory()->create();
    views($photo)->record();

    $view = View::where('viewable_id', $photo->id)->where('viewable_type', $photo->getMorphClass())->firstOrFail();

    expect($view->referrer)->toBe('https://www.google.com/search?q=foo')
        ->and($view->user_agent)->toBe('Mozilla/5.0 Test Browser');
});

test('it leaves referrer null when no referer header is sent', function () {
    $photo = Photo::factory()->create();
    views($photo)->record();

    $view = View::where('viewable_id', $photo->id)->where('viewable_type', $photo->getMorphClass())->firstOrFail();

    expect($view->referrer)->toBeNull();
});
