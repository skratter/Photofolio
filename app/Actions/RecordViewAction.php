<?php

namespace App\Actions;

use CyrildeWit\EloquentViewable\Contracts\Viewable;
use Illuminate\Support\Facades\Auth;

class RecordViewAction
{
    public function execute(Viewable $viewable): void
    {
        if (Auth::check()) {
            return;
        }

        views($viewable)->record();
    }
}
