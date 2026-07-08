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

        // Not the package's own `honor_dnt` config: it looks up the header
        // under the key "HTTP_DNT", but Symfony's normalized header bag only
        // ever exposes it as "DNT" - so that setting never actually triggers.
        if (request()->header('DNT') === '1') {
            return;
        }

        views($viewable)->record();
    }
}
