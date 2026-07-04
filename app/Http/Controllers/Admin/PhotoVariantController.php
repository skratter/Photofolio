<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PhotoVariantController extends Controller
{
    public function __invoke(Photo $photo, string $variant): BinaryFileResponse
    {
        abort_unless($photo->isProcessed(), 404);

        $path = match ($variant) {
            'thumb' => $photo->thumbPath(),
            'display' => $photo->displayPath(),
        };

        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
