<?php

namespace App\Livewire;

use App\Models\Album;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * @property-read Collection<int, Album> $albums
 */
#[Layout('layouts.public')]
class AlbumIndex extends Component
{
    /**
     * @return Collection<int, Album>
     */
    #[Computed]
    public function albums(): Collection
    {
        return Album::query()
            ->where('visibility', 'public')
            ->withCount('photos')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();
    }

    public function render(): View
    {
        return view('livewire.album-index')->title('Alben');
    }
}
