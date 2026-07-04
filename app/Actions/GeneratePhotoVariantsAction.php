<?php

namespace App\Actions;

use App\Models\Photo;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class GeneratePhotoVariantsAction
{
    private const DISPLAY_MAX_EDGE = 1024;
    private const THUMB_MAX_EDGE = 400;
    private const DISPLAY_QUALITY = 80;
    private const THUMB_QUALITY = 75;

    public function execute(Photo $photo): void
    {
        // v4 API: manager is created via the static usingDriver() factory method,
        // not via "new ImageManager(...)" as in v2/v3.
        $manager = ImageManager::usingDriver(Driver::class);

        $originalPath = storage_path(
            "app/photos/{$photo->id}/original." . pathinfo($photo->original_filename, PATHINFO_EXTENSION)
        );

        // v4 renamed read() to decode().
        $image = $manager->decode($originalPath);

        // Auto-orientation is applied by default in v4, but calling it explicitly
        // keeps this correct even if the global config ever disables the default.
        $image->orient();

        // Persist actual original dimensions now that the file is decoded
        // (post-orient, so width/height reflect the visually correct orientation).
        $photo->update([
            'width' => $image->width(),
            'height' => $image->height(),
        ]);

        $this->saveVariant(clone $image, $photo, 'display', self::DISPLAY_MAX_EDGE, self::DISPLAY_QUALITY);
        $this->saveVariant(clone $image, $photo, 'thumb', self::THUMB_MAX_EDGE, self::THUMB_QUALITY);

        $photo->update(['processed_at' => now()]);
    }

    private function saveVariant(
        \Intervention\Image\Interfaces\ImageInterface $image,
        Photo $photo,
        string $name,
        int $maxEdge,
        int $quality
    ): void {
        // scaleDown respects aspect ratio and only downsizes (never upscales small originals).
        $image->scaleDown(width: $maxEdge, height: $maxEdge);

        $path = storage_path("app/photos/{$photo->id}/{$name}.webp");

        // v4 dropped toWebp(); save() infers the target format from the file extension.
        $image->save($path, quality: $quality);
    }
}