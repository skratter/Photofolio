<?php

namespace App\Support;

use Composer\Script\Event;

class ComposerHooks
{
    public static function boostUpdate(Event $event): void
    {
        if (! is_dir(dirname(__DIR__, 2).'/vendor/laravel/boost')) {
            return;
        }

        passthru('php artisan boost:update --ansi');
    }
}
