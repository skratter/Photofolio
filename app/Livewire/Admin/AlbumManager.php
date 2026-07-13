<?php

namespace App\Livewire\Admin;

use App\Livewire\Forms\AlbumForm;
use App\Models\Album;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class AlbumManager extends Component
{
    public AlbumForm $form;

    public bool $showModal = false;

    public ?int $editingAlbumId = null;

    public ?int $confirmingDeleteId = null;

    // Bumped every time the modal opens so the Trix editor's wrapping element
    // gets a fresh wire:key - Trix only reads its starting content once when
    // the custom element is created, so reusing the same DOM node across two
    // "new album" attempts would leave the previous draft's text behind.
    public int $formInstance = 0;

    /**
     * @return Collection<int, Album>
     */
    #[Computed]
    public function albums(): Collection
    {
        return Album::withCount('photos')->orderBy('sort_order')->orderBy('title')->get();
    }

    public function openCreateModal(): void
    {
        $this->form->reset();
        $this->editingAlbumId = null;
        $this->formInstance++;
        $this->showModal = true;
    }

    public function openEditModal(int $albumId): void
    {
        $album = Album::findOrFail($albumId);
        $this->form->setAlbum($album);
        $this->editingAlbumId = $albumId;
        $this->formInstance++;
        $this->showModal = true;
    }

    public function save(): void
    {
        if ($this->editingAlbumId === null) {
            $this->form->store();
        } else {
            $this->form->update();
        }

        $this->showModal = false;
        unset($this->albums);
    }

    public function confirmDelete(int $albumId): void
    {
        $this->confirmingDeleteId = $albumId;
    }

    public function delete(): void
    {
        Album::findOrFail($this->confirmingDeleteId)->delete();
        $this->confirmingDeleteId = null;
        unset($this->albums);
    }
}
