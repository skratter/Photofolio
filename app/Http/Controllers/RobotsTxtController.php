<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsTxtController extends Controller
{
    public function __invoke(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /cron',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines)."\n")
            ->header('Content-Type', 'text/plain; charset=utf-8');
    }
}
