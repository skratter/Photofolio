<?php

namespace App\Http\Controllers;

use App\Actions\RecordViewAction;
use App\Models\Album;
use App\Models\Page;
use Illuminate\Contracts\View\View;

class WelcomeController extends Controller
{
    /**
     * Number of photos shown on the homepage at once - the homepage album
     * can hold far more than makes sense to show on a first impression, so a
     * random subset is picked on every page load. The rest is sent along as
     * a pool the page slowly rotates in from client-side, fading photos
     * currently on screen out for ones that aren't shown yet.
     */
    private const PHOTO_COUNT = 12;

    public function __invoke(RecordViewAction $recordView): View
    {
        $recordView->execute(Page::forSlug('welcome'));

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
            'homepagePhotos' => $shuffledPhotos?->take(self::PHOTO_COUNT)->values(),
            'homepagePhotoPool' => $shuffledPhotos?->slice(self::PHOTO_COUNT)->values(),
        ]);
    }
}
