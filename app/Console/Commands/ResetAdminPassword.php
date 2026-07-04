<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ResetAdminPassword extends Command
{
    protected $signature = 'admin:reset-password {email} {password}';

    protected $description = 'Reset the password for the given admin user';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->error("No user found with email {$this->argument('email')}");

            return self::FAILURE;
        }

        $user->update(['password' => Hash::make($this->argument('password'))]);
        $this->info("Password updated for {$user->email}");

        return self::SUCCESS;
    }
}
