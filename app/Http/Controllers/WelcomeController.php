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

        $homepageAlbum = Album::where('is_homepage', true)->first();

        return view('pages.welcome', [
            'homepageAlbum' => $homepageAlbum,
            'homepagePhotos' => $homepageAlbum?->photos()->processed()->orderBy('sort_order')->get(),
        ]);
    }
}
