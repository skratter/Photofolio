<?php

namespace App\Listeners;

use CyrildeWit\EloquentViewable\Events\ViewRecorded;
use CyrildeWit\EloquentViewable\View;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RecordViewMetadata
{
    public function __construct(private Request $request)
    {
        //
    }

    public function handle(ViewRecorded $event): void
    {
        $view = $event->view;

        if (! $view instanceof View) {
            return;
        }

        $view->update([
            'referrer' => $this->request->headers->get('referer'),
            'user_agent' => Str::limit((string) $this->request->userAgent(), 512, ''),
        ]);
    }
}
