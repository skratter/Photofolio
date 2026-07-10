<?php

namespace App\Livewire\Forms;

use App\Models\Album;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

class AlbumForm extends Form
{
    public ?Album $album = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('required|string|max:255|alpha_dash')]
    public string $slug = '';

    #[Validate('nullable|string|max:2000')]
    public string $description = '';

    #[Validate('required|in:public,private')]
    public string $visibility = 'public';

    // Left empty on edit = keep existing password. Required only when creating a private album.
    public string $password = '';

    #[Validate('required|integer|min:0')]
    public int $sort_order = 0;

    public bool $is_homepage = false;

    public bool $downloads_enabled = true;

    public function setAlbum(Album $album): void
    {
        $this->album = $album;
        $this->title = $album->title;
        $this->slug = $album->slug;
        $this->description = $album->description ?? '';
        $this->visibility = $album->visibility;
        $this->sort_order = $album->sort_order;
        $this->password = '';
        $this->is_homepage = $album->is_homepage;
        $this->downloads_enabled = $album->downloads_enabled;
    }

    public function generateSlugFromTitle(): void
    {
        // Only auto-fill on create; on edit the slug is already set and user-controlled,
        // overwriting it silently would break existing shared links.
        if ($this->album === null) {
            $this->slug = Str::slug($this->title);
        }
    }

    public function store(): Album
    {
        $this->validateSlugUniqueness();
        $this->validatePasswordRequiredForPrivate();

        $album = Album::create([
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description ?: null,
            'visibility' => $this->visibility,
            'sort_order' => $this->sort_order,
            'is_homepage' => $this->visibility === 'public' && $this->is_homepage,
            'downloads_enabled' => $this->downloads_enabled,
        ]);

        if ($this->visibility === 'private' && $this->password !== '') {
            $album->setPassword($this->password);
            $album->save();
        }

        return $album;
    }

    public function update(): Album
    {
        $this->validateSlugUniqueness();
        $this->validatePasswordRequiredForPrivate();

        $this->album->update([
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description ?: null,
            'visibility' => $this->visibility,
            'sort_order' => $this->sort_order,
            'is_homepage' => $this->visibility === 'public' && $this->is_homepage,
            'downloads_enabled' => $this->downloads_enabled,
        ]);

        // Only touch the password if a new one was actually entered.
        if ($this->password !== '') {
            $this->album->setPassword($this->password);
            $this->album->save();
        }

        return $this->album;
    }

    private function validateSlugUniqueness(): void
    {
        $this->validate([
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('albums', 'slug')->ignore($this->album?->id),
            ],
        ]);
    }

    private function validatePasswordRequiredForPrivate(): void
    {
        // Creating a private album without ever setting a password would lock it
        // permanently, since there's no fallback access path (no user accounts).
        $isNewPrivateAlbum = $this->album === null && $this->visibility === 'private';
        $isExistingAlbumSwitchingToPrivateWithoutPassword =
            $this->album !== null
            && $this->visibility === 'private'
            && $this->album->password_hash === null
            && $this->password === '';

        if (($isNewPrivateAlbum || $isExistingAlbumSwitchingToPrivateWithoutPassword) && $this->password === '') {
            $this->addError('password', 'Ein Passwort ist für private Alben erforderlich.');
            throw ValidationException::withMessages([
                'password' => 'Ein Passwort ist für private Alben erforderlich.',
            ]);
        }
    }
}
