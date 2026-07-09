<?php

namespace App\Livewire;

use App\Actions\RecordViewAction;
use App\Models\Album;
use App\Models\Photo;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.public')]
class PhotoShow extends Component
{
    public Album $album;

    public Photo $photo;

    public function mount(): void
    {
        abort_unless($this->photo->album_id === $this->album->id, 404);
        abort_unless($this->album->isAccessible(), 404);
        abort_unless($this->photo->isProcessed(), 404);

        app(RecordViewAction::class)->execute($this->photo);
    }

    #[Computed]
    public function previousPhoto(): ?Photo
    {
        return $this->album->photos()
            ->where('sort_order', '<', $this->photo->sort_order)
            ->orderByDesc('sort_order')
            ->first();
    }

    #[Computed]
    public function nextPhoto(): ?Photo
    {
        return $this->album->photos()
            ->where('sort_order', '>', $this->photo->sort_order)
            ->orderBy('sort_order')
            ->first();
    }

    public function render(): View
    {
        return view('livewire.photo-show')->title($this->photo->title ?: $this->album->title);
    }
}
