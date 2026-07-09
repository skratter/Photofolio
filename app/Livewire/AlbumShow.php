<?php

namespace App\Livewire;

use App\Actions\RecordViewAction;
use App\Models\Album;
use App\Models\Photo;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * @property-read Collection<int, Photo> $photos
 */
#[Layout('layouts.public')]
class AlbumShow extends Component
{
    public Album $album;

    public string $password = '';

    public function mount(): void
    {
        if ($this->album->isAccessible()) {
            app(RecordViewAction::class)->execute($this->album);
        }
    }

    public function unlock(): void
    {
        if (! $this->album->checkPassword($this->password)) {
            $this->addError('password', 'Falsches Passwort.');

            return;
        }

        session()->put($this->album->unlockSessionKey(), true);
        $this->password = '';

        app(RecordViewAction::class)->execute($this->album);
    }

    /**
     * @return Collection<int, Photo>
     */
    #[Computed]
    public function photos(): Collection
    {
        if (! $this->album->isAccessible()) {
            return new Collection;
        }

        return $this->album->photos()->processed()->orderBy('sort_order')->get();
    }

    public function render(): View
    {
        return view('livewire.album-show')->title($this->album->title);
    }
}
