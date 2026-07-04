<?php

namespace App\Livewire\Admin;

use App\Actions\BuildPhotoZipAction;
use App\Models\Album;
use App\Models\Photo;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.admin')]
class AlbumShow extends Component
{
    public Album $album;

    /** @var array<int, int> */
    public array $selectedPhotoIds = [];

    public bool $showBulkRenameModal = false;

    public string $bulkRenameBaseName = '';

    #[Computed]
    public function photos()
    {
        return $this->album->photos()->orderBy('sort_order')->get();
    }

    #[On('photos-uploaded')]
    public function refreshPhotos(): void
    {
        unset($this->photos);
    }

    public function deletePhoto(int $photoId): void
    {
        $photo = Photo::where('album_id', $this->album->id)->findOrFail($photoId);

        File::deleteDirectory($photo->directoryPath());
        $photo->delete();

        $this->selectedPhotoIds = array_values(array_diff($this->selectedPhotoIds, [$photoId]));

        unset($this->photos);
    }

    public function toggleSelectAll(): void
    {
        $this->selectedPhotoIds = count($this->selectedPhotoIds) === $this->photos->count()
            ? []
            : $this->photos->pluck('id')->all();
    }

    public function bulkDelete(): void
    {
        $photos = Photo::where('album_id', $this->album->id)
            ->whereIn('id', $this->selectedPhotoIds)
            ->get();

        foreach ($photos as $photo) {
            File::deleteDirectory($photo->directoryPath());
            $photo->delete();
        }

        $this->selectedPhotoIds = [];

        unset($this->photos);
    }

    public function openBulkRenameModal(): void
    {
        $this->bulkRenameBaseName = '';
        $this->showBulkRenameModal = true;
    }

    public function bulkRename(): void
    {
        $this->validate([
            'bulkRenameBaseName' => 'required|string|max:255',
        ]);

        $photos = Photo::where('album_id', $this->album->id)
            ->whereIn('id', $this->selectedPhotoIds)
            ->orderBy('sort_order')
            ->get();

        foreach ($photos as $index => $photo) {
            $photo->update(['title' => "{$this->bulkRenameBaseName} ".($index + 1)]);
        }

        $this->showBulkRenameModal = false;
        $this->selectedPhotoIds = [];

        unset($this->photos);
    }

    public function download(BuildPhotoZipAction $buildZip)
    {
        $photos = Photo::where('album_id', $this->album->id)
            ->whereIn('id', $this->selectedPhotoIds)
            ->orderBy('sort_order')
            ->get();

        abort_if($photos->isEmpty(), 404);

        if ($photos->count() === 1) {
            $photo = $photos->first();

            return response()->download($photo->originalPath(), $photo->downloadFilename());
        }

        $zipPath = $buildZip->execute($photos);

        return response()->download($zipPath, Str::slug($this->album->title).'.zip')->deleteFileAfterSend();
    }

    public function render()
    {
        return view('livewire.admin.album-show');
    }
}
