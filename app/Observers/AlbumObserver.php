<?php

namespace App\Observers;

use App\Models\Album;

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
}