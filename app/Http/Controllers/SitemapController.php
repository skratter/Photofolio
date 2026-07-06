<?php

namespace App\Http\Controllers;

use App\Models\Album;
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

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
