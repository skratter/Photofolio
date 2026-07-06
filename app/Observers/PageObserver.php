<?php

namespace App\Observers;

use App\Models\Page;

class PageObserver
{
    public function deleting(Page $page): void
    {
        // Delete through Eloquent (not the DB cascade) so each attachment's
        // own deleting observer runs and removes its file from disk too.
        foreach ($page->attachments as $attachment) {
            $attachment->delete();
        }
    }
}
