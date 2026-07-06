<?php

namespace App\Livewire\Admin;

use App\Models\Album;
use App\Models\Page;
use App\Models\Photo;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Dashboard extends Component
{
    #[Computed]
    public function albumsCount(): int
    {
        return Album::count();
    }

    #[Computed]
    public function photosCount(): int
    {
        return Photo::count();
    }

    #[Computed]
    public function homepageViewsCount(): int
    {
        return views(Page::forSlug('welcome'))->count();
    }

    #[Computed]
    public function topAlbum(): ?Album
    {
        return Album::with('coverPhoto')->orderByViews()->first();
    }

    #[Computed]
    public function topPhoto(): ?Photo
    {
        return Photo::with('album')->orderByViews()->first();
    }

    public function render(): View
    {
        return view('livewire.admin.dashboard');
    }
}
