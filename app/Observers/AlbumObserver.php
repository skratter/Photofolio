<?php

namespace App\Observers;

use App\Models\Album;
use Illuminate\Support\Facades\File;

class AlbumObserver
{
    public function saving(Album $album): void
    {
        if ($album->is_homepage) {
            Album::where('id', '!=', $album->id)
                ->where('is_homepage', true)
                ->update(['is_homepage' => false]);
        }
    }

    public function deleting(Album $album): void
    {
        // Delete each photo's storage directory before the cascade delete
        // removes the DB rows - otherwise we lose the photo IDs needed to
        // build the storage paths.
        foreach ($album->photos as $photo) {
            File::deleteDirectory($photo->directoryPath());
        }
    }
}
