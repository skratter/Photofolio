<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Page;
use App\Models\Photo;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = [
            ['loc' => url('/')],
        ];

        if (Route::has('albums.show')) {
            foreach (Album::query()->where('visibility', 'public')->get() as $album) {
                $urls[] = [
                    'loc' => route('albums.show', $album->slug),
                    'lastmod' => $album->updated_at?->toAtomString(),
                ];
            }
        }

        // No public single-photo page exists yet - this starts working on its
        // own once one is added under this route name, same as albums above.
        if (Route::has('albums.photos.show')) {
            foreach (Photo::with('album')->whereHas('album', fn ($query) => $query->where('visibility', 'public'))->get() as $photo) {
                $urls[] = [
                    'loc' => route('albums.photos.show', [$photo->album, $photo]),
                    'lastmod' => $photo->updated_at?->toAtomString(),
                ];
            }
        }

        foreach (Page::published()->get() as $page) {
            $urls[] = [
                'loc' => route('pages.show', $page->slug),
                'lastmod' => $page->updated_at?->toAtomString(),
            ];
        }

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
