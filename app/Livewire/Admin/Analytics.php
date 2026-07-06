<?php

namespace App\Livewire\Admin;

use App\Models\Album;
use App\Models\Page;
use App\Models\Photo;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Analytics extends Component
{
    /**
     * @return array{total: int, unique: int}
     */
    #[Computed]
    public function homepageViews(): array
    {
        $page = Page::forSlug('welcome');

        return [
            'total' => views($page)->count(),
            'unique' => views($page)->unique()->count(),
        ];
    }

    /**
     * @return Collection<int, Album>
     */
    #[Computed]
    public function albums(): Collection
    {
        return Album::orderByViews()->withViewsCount(unique: true, as: 'unique_views_count')->limit(20)->get();
    }

    /**
     * @return Collection<int, Photo>
     */
    #[Computed]
    public function photos(): Collection
    {
        return Photo::with('album')->orderByViews()->withViewsCount(unique: true, as: 'unique_views_count')->limit(20)->get();
    }

    /**
     * @return Collection<int, Page>
     */
    #[Computed]
    public function pages(): Collection
    {
        // Excludes the homepage, which already gets its own cards above.
        return Page::where('slug', '!=', 'welcome')
            ->orderByViews()
            ->withViewsCount(unique: true, as: 'unique_views_count')
            ->limit(20)
            ->get();
    }

    public function render(): View
    {
        return view('livewire.admin.analytics');
    }
}
