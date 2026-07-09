<?php

namespace App\Http\Controllers;

use App\Actions\RecordViewAction;
use App\Models\Album;
use App\Models\Page;
use App\Models\Setting;
use Illuminate\Contracts\View\View;

class WelcomeController extends Controller
{
    public function __invoke(RecordViewAction $recordView): View
    {
        $recordView->execute(Page::forSlug('welcome'));

        $settings = Setting::current();

        // Guards against a private album ever being shown here unprotected -
        // the homepage has no password gate of its own, unlike /album/{slug}.
        $homepageAlbum = Album::where('is_homepage', true)->where('visibility', 'public')->first();

        // reorder() clears the photos() relation's default "order by
        // sort_order" first - inRandomOrder() alone would only ever append
        // RAND() as a tiebreaker after it, which never applies since
        // sort_order values are unique, silently making the "random" order
        // just the normal fixed one.
        $shuffledPhotos = $homepageAlbum?->photos()->processed()->reorder()->inRandomOrder()->get();

        return view('pages.welcome', [
            'homepageAlbum' => $homepageAlbum,
            'homepagePhotos' => $shuffledPhotos?->take($settings->homepage_photo_count)->values(),
            'homepagePhotoPool' => $shuffledPhotos?->slice($settings->homepage_photo_count)->values(),
            'homepageRotateSeconds' => $settings->homepage_rotate_seconds,
            // The layout's own View::composer only shares this with
            // components.layouts.public's internal scope (header/footer),
            // not with slot content passed into it from this view - so this
            // page needs its own copy for the placeholder text below.
            'siteSettings' => $settings,
        ]);
    }
}
