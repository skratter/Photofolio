<?php

namespace App\Jobs;

use App\Actions\ExtractExifDataAction;
use App\Actions\GeneratePhotoVariantsAction;
use App\Models\Photo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessUploadedPhoto implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly Photo $photo
    ) {
    }

    public function handle(ExtractExifDataAction $extractExif, GeneratePhotoVariantsAction $generateVariants): void
    {
        $extractExif->execute($this->photo);
        $generateVariants->execute($this->photo);
    }
}