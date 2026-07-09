<?php

namespace App\Http\Controllers;

use App\Actions\RecordViewAction;
use App\Models\Album;
use App\Models\Page;
use Illuminate\Contracts\View\View;

class WelcomeController extends Controller
{
    public function __invoke(RecordViewAction $recordView): View
    {
        $recordView->execute(Page::forSlug('welcome'));

        // Guards against a private album ever being shown here unprotected -
        // the homepage has no password gate of its own, unlike /album/{slug}.
        $homepageAlbum = Album::where('is_homepage', true)->where('visibility', 'public')->first();

        return view('pages.welcome', [
            'homepageAlbum' => $homepageAlbum,
            'homepagePhotos' => $homepageAlbum?->photos()->processed()->orderBy('sort_order')->get(),
        ]);
    }
}
