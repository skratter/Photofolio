<?php

namespace App\Livewire\Admin;

use App\Livewire\Forms\PhotoForm;
use App\Models\Album;
use App\Models\Photo;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class PhotoShow extends Component
{
    public Album $album;

    public Photo $photo;

    public PhotoForm $form;

    public function mount(): void
    {
        abort_unless($this->photo->album_id === $this->album->id, 404);

        $this->form->setPhoto($this->photo);
    }

    public function save(): void
    {
        $this->form->update();

        Flux::toast(text: 'Foto gespeichert.', variant: 'success');
    }

    public function render(): View
    {
        return view('livewire.admin.photo-show');
    }
}
