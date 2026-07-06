<?php

namespace App\Observers;

use App\Models\PageAttachment;
use Illuminate\Support\Facades\Storage;

class PageAttachmentObserver
{
    public function deleting(PageAttachment $attachment): void
    {
        Storage::disk($attachment->disk)->delete($attachment->path);
    }
}
