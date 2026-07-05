<?php

use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    config(['app.cron.user' => 'cron-user', 'app.cron.password' => 'secret-pass']);
});

test('rejects requests without credentials', function () {
    $response = $this->get(route('cron.schedule-run'));

    $response->assertForbidden();
});

test('rejects requests with wrong credentials', function () {
    $response = $this->withBasicAuth('wrong', 'wrong')->get(route('cron.schedule-run'));

    $response->assertForbidden();
});

test('processes the queue when credentials are valid', function () {
    Artisan::shouldReceive('call')
        ->once()
        ->with('queue:work', [
            '--stop-when-empty' => true,
            '--max-time' => 50,
            '--tries' => 3,
        ])
        ->andReturn(0);

    $response = $this->withBasicAuth('cron-user', 'secret-pass')->get(route('cron.schedule-run'));

    $response->assertOk()->assertSee('OK');
});

test('aborts when cron credentials are not configured', function () {
    config(['app.cron.user' => '', 'app.cron.password' => '']);

    $response = $this->withBasicAuth('cron-user', 'secret-pass')->get(route('cron.schedule-run'));

    $response->assertStatus(500);
});
