<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Photo;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PhotoDownloadController extends Controller
{
    public function __invoke(Album $album, Photo $photo): BinaryFileResponse
    {
        abort_unless($photo->album_id === $album->id, 404);
        abort_unless($album->isAccessible(), 404);
        abort_unless($album->downloads_enabled, 404);
        abort_unless($photo->isProcessed(), 404);
        abort_unless(is_file($photo->originalPath()), 404);

        return response()->download($photo->originalPath(), $photo->downloadFilename());
    }
}
