<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Photo;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PhotoVariantController extends Controller
{
    public function __invoke(Album $album, Photo $photo, string $variant): BinaryFileResponse
    {
        abort_unless($photo->album_id === $album->id, 404);
        abort_unless($album->isAccessible(), 404);
        abort_unless($photo->isProcessed(), 404);

        $path = match ($variant) {
            'thumb' => $photo->thumbPath(),
            'display' => $photo->displayPath(),
            default => abort(404),
        };

        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
