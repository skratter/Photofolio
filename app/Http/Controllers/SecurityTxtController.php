<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SecurityTxtController extends Controller
{
    public function __invoke(): Response
    {
        $lines = [
            'Contact: mailto:'.config('app.admin.email'),
            'Expires: '.now()->addYear()->toRfc3339String(),
            'Canonical: '.url('/.well-known/security.txt'),
        ];

        return response(implode("\n", $lines)."\n")
            ->header('Content-Type', 'text/plain; charset=utf-8');
    }
}
