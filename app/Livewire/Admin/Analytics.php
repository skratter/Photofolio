<?php

namespace App\Livewire\Admin;

use App\Models\Album;
use App\Models\Page;
use App\Models\Photo;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Analytics extends Component
{
    #[Computed]
    public function homepageViews(): array
    {
        $page = Page::forKey('welcome');

        return [
            'total' => views($page)->count(),
            'unique' => views($page)->unique()->count(),
        ];
    }

    #[Computed]
    public function albums()
    {
        return Album::orderByViews()->withViewsCount(unique: true, as: 'unique_views_count')->limit(20)->get();
    }

    #[Computed]
    public function photos()
    {
        return Photo::with('album')->orderByViews()->withViewsCount(unique: true, as: 'unique_views_count')->limit(20)->get();
    }

    public function render()
    {
        return view('livewire.admin.analytics');
    }
}
