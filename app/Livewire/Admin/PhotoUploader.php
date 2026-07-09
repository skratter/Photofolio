<?php

namespace App\Livewire\Admin;

use App\Jobs\ProcessUploadedPhoto;
use App\Models\Album;
use App\Models\Photo;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class PhotoUploader extends Component
{
    use WithFileUploads;

    public Album $album;

    /** @var array<int, TemporaryUploadedFile> */
    public array $photos = [];

    /**
     * Validates and stores each file individually instead of the whole batch
     * at once - the JS side already sends files in small batches (to stay
     * under PHP's max_file_uploads per request), and a single bad file
     * shouldn't hide the outcome of every other file in the same batch. The
     * per-file results are reported back to the browser so the upload list
     * can show which files actually made it in.
     */
    public function updatedPhotos(): void
    {
        $results = [];

        foreach ($this->photos as $file) {
            $name = $file->getClientOriginalName();

            $validator = Validator::make(
                ['photo' => $file],
                ['photo' => 'image|mimes:jpg,jpeg,png,webp|max:20480'],
            );

            if ($validator->fails()) {
                $results[] = ['name' => $name, 'status' => 'error'];

                continue;
            }

            $this->storePhoto($file);
            $results[] = ['name' => $name, 'status' => 'done'];
        }

        $this->photos = [];
        $this->dispatch('photos-uploaded');
        $this->dispatch('batch-uploaded', results: $results);
    }

    private function storePhoto(TemporaryUploadedFile $file): void
    {
        $extension = $file->getClientOriginalExtension();

        $nextSortOrder = (int) $this->album->photos()->max('sort_order') + 1;

        $photo = Photo::create([
            'album_id' => $this->album->id,
            'original_filename' => $file->getClientOriginalName(),
            'disk_path' => '',
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'width' => 0,
            'height' => 0,
            'sort_order' => $nextSortOrder,
        ]);

        // storeAs() (not move()) because the file may be a Livewire test/fake
        // upload that was never registered as a real PHP upload - move_uploaded_file()
        // would reject it. storeAs() writes through the "photos" disk instead.
        $file->storeAs((string) $photo->id, "original.{$extension}", ['disk' => 'photos']);

        $photo->update(['disk_path' => "photos/{$photo->id}/original.{$extension}"]);

        // storeAs() copies across disks rather than moving, so the temp upload
        // is left behind in livewire-tmp otherwise - clean it up immediately
        // rather than waiting on Livewire's automatic 24h sweep.
        $file->delete();

        ProcessUploadedPhoto::dispatch($photo);
    }

    public function render(): View
    {
        return view('livewire.admin.photo-uploader');
    }
}
