<?php

namespace App\Livewire;

use App\Actions\RecordViewAction;
use App\Models\Page;
use App\Models\PageAttachment;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.public')]
class PageShow extends Component
{
    public Page $page;

    public function mount(): void
    {
        abort_unless($this->page->isPublished(), 404);

        app(RecordViewAction::class)->execute($this->page);
    }

    /**
     * @return Collection<int, PageAttachment>
     */
    #[Computed]
    public function downloads(): Collection
    {
        return $this->page->attachments()->where('kind', 'download')->get();
    }

    public function render(): View
    {
        return view('livewire.page-show')->title($this->page->title ?: config('app.name'));
    }
}
