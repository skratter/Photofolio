<?php

namespace App\Actions;

use App\Models\Photo;
use Illuminate\Support\Collection;
use ZipArchive;

class BuildPhotoZipAction
{
    /**
     * @param  Collection<int, Photo>  $photos
     */
    public function execute(Collection $photos): string
    {
        $zipPath = tempnam(sys_get_temp_dir(), 'album-download-').'.zip';

        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $usedNames = [];
        $addedCount = 0;

        foreach ($photos as $photo) {
            if (! is_file($photo->originalPath())) {
                continue;
            }

            $zip->addFile($photo->originalPath(), $this->uniqueEntryName($photo, $usedNames));
            $addedCount++;
        }

        $zip->close();

        // On some libzip versions, closing an archive with zero entries deletes
        // the file entirely instead of writing an empty archive - guard against
        // handing back a path to a file that no longer exists.
        abort_if($addedCount === 0, 404);

        return $zipPath;
    }

    /**
     * @param  array<int, string>  $usedNames
     */
    private function uniqueEntryName(Photo $photo, array &$usedNames): string
    {
        $name = $photo->downloadFilename();

        if (! in_array($name, $usedNames, true)) {
            $usedNames[] = $name;

            return $name;
        }

        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $base = pathinfo($name, PATHINFO_FILENAME);
        $name = "{$base}-{$photo->id}.{$extension}";
        $usedNames[] = $name;

        return $name;
    }
}
