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
            $albums = Album::query()
                ->where('visibility', 'public')
                ->with(['photos' => fn ($query) => $query->processed()])
                ->get();

            foreach ($albums as $album) {
                $urls[] = [
                    'loc' => route('albums.show', $album->slug),
                    'lastmod' => $album->updated_at?->toAtomString(),
                    // Google's image sitemap extension: listing every photo
                    // shown on the album page as an <image:image> entry helps
                    // them get indexed by image search, which for a photo
                    // portfolio matters at least as much as web search.
                    'images' => Route::has('albums.photos.display')
                        ? $album->photos->map(fn ($photo) => route('albums.photos.display', [$album, $photo]))->all()
                        : [],
                ];
            }
        }

        if (Route::has('albums.photos.show')) {
            $photos = Photo::with('album')
                ->processed()
                ->whereHas('album', fn ($query) => $query->where('visibility', 'public'))
                ->get();

            foreach ($photos as $photo) {
                $urls[] = [
                    'loc' => route('albums.photos.show', [$photo->album, $photo]),
                    'lastmod' => $photo->updated_at?->toAtomString(),
                    'images' => [route('albums.photos.display', [$photo->album, $photo])],
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
