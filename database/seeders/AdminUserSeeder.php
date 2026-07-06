<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => config('app.admin.email')],
            [
                'name' => config('app.admin.name'),
                'password' => bcrypt((string) config('app.admin.password')),
            ]
        );
    }
}
