<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Silently does nothing without ADMIN_EMAIL set - otherwise this would
     * create a real, login-capable user with an empty email/password on any
     * install that runs db:seed before filling in the admin .env values.
     */
    public function run(): void
    {
        $email = config('app.admin.email');

        if (! $email) {
            return;
        }

        User::firstOrCreate(
            ['email' => $email],
            [
                'name' => config('app.admin.name') ?: 'Admin',
                'password' => bcrypt((string) config('app.admin.password')),
            ]
        );
    }
}
