<?php

namespace App\Livewire;

use App\Actions\RecordViewAction;
use App\Models\Album;
use App\Models\Photo;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * @property-read Collection<int, Photo> $photos
 */
#[Layout('layouts.public')]
class AlbumShow extends Component
{
    private const PER_PAGE = 30;

    public Album $album;

    public string $password = '';

    #[Url(as: 'seite')]
    public int $page = 1;

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

    public function goToPage(int $page): void
    {
        $this->page = max(1, min($page, $this->totalPages()));
        unset($this->photos);
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

        return $this->album->photos()->processed()
            ->orderBy('sort_order')
            ->forPage($this->page, self::PER_PAGE)
            ->get();
    }

    #[Computed]
    public function totalPhotoCount(): int
    {
        return $this->album->isAccessible() ? $this->album->photos()->processed()->count() : 0;
    }

    #[Computed]
    public function totalPages(): int
    {
        return (int) max(1, ceil($this->totalPhotoCount() / self::PER_PAGE));
    }

    public function render(): View
    {
        return view('livewire.album-show')->title($this->album->title);
    }
}
