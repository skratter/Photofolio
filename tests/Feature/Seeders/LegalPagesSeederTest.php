<?php

use App\Models\Page;
use Database\Seeders\LegalPagesSeeder;

test('it creates the impressum and datenschutz pages', function () {
    $this->seed(LegalPagesSeeder::class);

    $impressum = Page::where('slug', 'impressum')->first();
    $datenschutz = Page::where('slug', 'datenschutz')->first();

    expect($impressum)->not->toBeNull()
        ->and($impressum->type)->toBe('legal')
        ->and($impressum->status)->toBe('published')
        ->and($impressum->isDeletable())->toBeFalse()
        ->and($datenschutz)->not->toBeNull()
        ->and($datenschutz->type)->toBe('legal')
        ->and($datenschutz->isDeletable())->toBeFalse();
});

test('it is idempotent when run more than once', function () {
    $this->seed(LegalPagesSeeder::class);
    $this->seed(LegalPagesSeeder::class);

    expect(Page::where('slug', 'impressum')->count())->toBe(1)
        ->and(Page::where('slug', 'datenschutz')->count())->toBe(1);
});
