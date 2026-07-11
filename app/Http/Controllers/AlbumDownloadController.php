<?php

namespace App\Http\Controllers;

use App\Actions\BuildPhotoZipAction;
use App\Models\Album;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AlbumDownloadController extends Controller
{
    public function __invoke(Request $request, Album $album, BuildPhotoZipAction $buildZip): BinaryFileResponse
    {
        abort_unless($album->isAccessible(), 404);
        abort_unless($album->downloads_enabled, 404);

        $query = $album->photos()->processed();

        // "ids" narrows the download to a manually picked selection (see the
        // album's selection mode) instead of the whole album - scoped
        // through the album's own photos() relation, so ids belonging to
        // another album are silently ignored rather than leaking photos
        // across albums.
        if ($request->filled('ids')) {
            $ids = array_filter(array_map('intval', explode(',', (string) $request->query('ids'))));

            abort_if($ids === [], 404);

            $query->whereIn('id', $ids);
        }

        $photos = $query->orderBy('sort_order')->get();

        abort_if($photos->isEmpty(), 404);

        if ($photos->count() === 1) {
            $photo = $photos->first();

            return response()->download($photo->originalPath(), $photo->downloadFilename());
        }

        $zipPath = $buildZip->execute($photos);

        return response()->download($zipPath, Str::slug($album->title).'.zip')->deleteFileAfterSend();
    }
}
