<?php

use Carbon\Carbon;

test('it exposes the admin email as contact and a future expiry date', function () {
    config(['app.admin.email' => 'security@example.com']);

    $response = $this->get(route('security-txt'));

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=utf-8')
        ->assertSee('Contact: mailto:security@example.com', false);

    preg_match('/Expires: (.+)/', $response->getContent(), $matches);

    expect($matches)->toHaveCount(2)
        ->and(Carbon::parse($matches[1])->isAfter(now()->addMonths(11)))->toBeTrue();
});
