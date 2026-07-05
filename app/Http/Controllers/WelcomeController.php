<?php

namespace App\Http\Controllers;

use App\Actions\RecordViewAction;
use App\Models\Page;
use Illuminate\Contracts\View\View;

class WelcomeController extends Controller
{
    public function __invoke(RecordViewAction $recordView): View
    {
        $recordView->execute(Page::forKey('welcome'));

        return view('pages.welcome');
    }
}
