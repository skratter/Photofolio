<?php

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Support\Facades\Hash;

test('it creates the admin user from the configured credentials', function () {
    config([
        'app.admin.email' => 'admin@example.com',
        'app.admin.name' => 'Admin',
        'app.admin.password' => 'super-secret',
    ]);

    $this->seed(AdminUserSeeder::class);

    $user = User::where('email', 'admin@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Admin')
        ->and(Hash::check('super-secret', $user->password))->toBeTrue();
});

test('it does nothing when no admin email is configured', function () {
    config(['app.admin.email' => null]);

    $this->seed(AdminUserSeeder::class);

    expect(User::count())->toBe(0);
});

test('it is idempotent when run more than once', function () {
    config([
        'app.admin.email' => 'admin@example.com',
        'app.admin.name' => 'Admin',
        'app.admin.password' => 'super-secret',
    ]);

    $this->seed(AdminUserSeeder::class);
    $this->seed(AdminUserSeeder::class);

    expect(User::where('email', 'admin@example.com')->count())->toBe(1);
});
