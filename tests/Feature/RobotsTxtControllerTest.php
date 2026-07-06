<?php

test('it disallows admin and cron paths and points to the sitemap', function () {
    $response = $this->get(route('robots'));

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=utf-8')
        ->assertSee('Disallow: /admin', false)
        ->assertSee('Disallow: /cron', false)
        ->assertSee('Sitemap: '.route('sitemap'), false);
});
