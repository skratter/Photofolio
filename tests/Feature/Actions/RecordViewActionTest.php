<?php

use App\Actions\RecordViewAction;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Http\Request;

test('it records a view for a guest visitor', function () {
    $photo = Photo::factory()->create();

    (new RecordViewAction)->execute($photo);

    expect(views($photo)->count())->toBe(1);
});

test('it does not record a view for an authenticated user', function () {
    $this->actingAs(User::factory()->create());
    $photo = Photo::factory()->create();

    (new RecordViewAction)->execute($photo);

    expect(views($photo)->count())->toBe(0);
});

test('it does not record a view for a known crawler', function () {
    app()->instance('request', Request::create('/', 'GET', server: [
        'HTTP_USER_AGENT' => 'Googlebot/2.1 (+http://www.google.com/bot.html)',
    ]));
    $photo = Photo::factory()->create();

    (new RecordViewAction)->execute($photo);

    expect(views($photo)->count())->toBe(0);
});
