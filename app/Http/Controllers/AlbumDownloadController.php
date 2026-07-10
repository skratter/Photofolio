<?php

namespace App\Http\Controllers;

use App\Actions\BuildPhotoZipAction;
use App\Models\Album;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AlbumDownloadController extends Controller
{
    public function __invoke(Album $album, BuildPhotoZipAction $buildZip): BinaryFileResponse
    {
        abort_unless($album->isAccessible(), 404);
        abort_unless($album->downloads_enabled, 404);

        $photos = $album->photos()->processed()->orderBy('sort_order')->get();

        abort_if($photos->isEmpty(), 404);

        if ($photos->count() === 1) {
            $photo = $photos->first();

            return response()->download($photo->originalPath(), $photo->downloadFilename());
        }

        $zipPath = $buildZip->execute($photos);

        return response()->download($zipPath, Str::slug($album->title).'.zip')->deleteFileAfterSend();
    }
}
