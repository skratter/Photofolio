<?php

namespace App\Livewire;

use App\Actions\RecordViewAction;
use App\Models\Album;
use App\Models\Photo;
use App\Models\Setting;
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
    public Album $album;

    public string $password = '';

    #[Url(as: 'seite')]
    public int $page = 1;

    public bool $selecting = false;

    /** @var array<int, int> */
    public array $selectedPhotoIds = [];

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

    public function startSelecting(): void
    {
        $this->selecting = true;
    }

    public function stopSelecting(): void
    {
        $this->selecting = false;
        $this->selectedPhotoIds = [];
    }

    public function toggleSelect(int $photoId): void
    {
        if (($key = array_search($photoId, $this->selectedPhotoIds, true)) !== false) {
            unset($this->selectedPhotoIds[$key]);
            $this->selectedPhotoIds = array_values($this->selectedPhotoIds);

            return;
        }

        $this->selectedPhotoIds[] = $photoId;
    }

    /**
     * Adds every photo on the current page to the selection, on top of
     * whatever was already selected on other pages.
     */
    public function selectAllOnPage(): void
    {
        $this->selectedPhotoIds = array_values(array_unique([
            ...$this->selectedPhotoIds,
            ...$this->photos->pluck('id')->all(),
        ]));
    }

    public function clearSelection(): void
    {
        $this->selectedPhotoIds = [];
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
            ->forPage($this->page, Setting::current()->album_photos_per_page)
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
        return (int) max(1, ceil($this->totalPhotoCount() / Setting::current()->album_photos_per_page));
    }

    public function render(): View
    {
        return view('livewire.album-show')->title($this->album->title);
    }
}
