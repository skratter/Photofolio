<?php

namespace App\Livewire\Admin;

use App\Actions\BuildViewHistoryAction;
use App\Actions\BuildViewOriginsAction;
use App\Models\Album;
use App\Models\Page;
use App\Models\Photo;
use CyrildeWit\EloquentViewable\Contracts\Viewable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Analytics extends Component
{
    public bool $showHistoryModal = false;

    public ?string $historyType = null;

    public ?int $historyId = null;

    /**
     * @return array{id: int, total: int, unique: int}
     */
    #[Computed]
    public function homepageViews(): array
    {
        $page = Page::forSlug('welcome');

        return [
            'id' => $page->id,
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

    public function showHistory(string $type, int $id): void
    {
        $this->historyType = $type;
        $this->historyId = $id;
        $this->showHistoryModal = true;
    }

    #[Computed]
    public function historyViewable(): ?Viewable
    {
        return match ($this->historyType) {
            'album' => Album::find($this->historyId),
            'photo' => Photo::find($this->historyId),
            'page' => Page::find($this->historyId),
            default => null,
        };
    }

    public function historyLabel(): ?string
    {
        $viewable = $this->historyViewable();

        return match (true) {
            $viewable instanceof Photo => $viewable->title ?: $viewable->original_filename,
            $viewable instanceof Page => $viewable->title ?: ($viewable->slug === 'welcome' ? 'Startseite' : $viewable->slug),
            $viewable instanceof Album => $viewable->title,
            default => null,
        };
    }

    /**
     * @return ?array{daily: \Illuminate\Support\Collection<int, array{label: string, count: int}>, monthly: \Illuminate\Support\Collection<int, array{label: string, count: int}>, yearly: \Illuminate\Support\Collection<int, array{label: string, count: int}>, total: int}
     */
    #[Computed]
    public function history(): ?array
    {
        $viewable = $this->historyViewable();

        return $viewable ? (new BuildViewHistoryAction)->execute($viewable) : null;
    }

    /**
     * @return ?array{referrers: \Illuminate\Support\Collection<int, array{label: string, count: int, isOwn: bool}>, userAgents: \Illuminate\Support\Collection<int, array{label: string, count: int}>, ownDomainsTotal: int, total: int}
     */
    #[Computed]
    public function origins(): ?array
    {
        $viewable = $this->historyViewable();

        return $viewable ? (new BuildViewOriginsAction)->execute($viewable) : null;
    }

    public function render(): View
    {
        return view('livewire.admin.analytics');
    }
}
