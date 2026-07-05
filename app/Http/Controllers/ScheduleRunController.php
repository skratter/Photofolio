<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class ScheduleRunController extends Controller
{
    public function __invoke(Request $request): string
    {
        $user = (string) config('app.cron.user');
        $password = (string) config('app.cron.password');

        abort_if($user === '' || $password === '', 500, 'CRON_USER / CRON_PASSWORD not configured.');

        abort_unless(
            hash_equals($user, (string) $request->getUser())
            && hash_equals($password, (string) $request->getPassword()),
            403
        );

        Artisan::call('schedule:run');

        return 'OK';
    }
}
