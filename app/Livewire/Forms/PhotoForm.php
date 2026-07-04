<?php

namespace App\Livewire\Forms;

use App\Models\Photo;
use App\Models\Tag;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Form;

class PhotoForm extends Form
{
    public ?Photo $photo = null;

    #[Validate('nullable|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:2000')]
    public string $notes = '';

    #[Validate('nullable|string|max:500')]
    public string $tags = '';

    public function setPhoto(Photo $photo): void
    {
        $this->photo = $photo;
        $this->title = $photo->title ?? '';
        $this->notes = $photo->notes ?? '';
        $this->tags = $photo->tags->pluck('name')->implode(', ');
    }

    public function update(): void
    {
        $this->validate();

        $this->photo->update([
            'title' => $this->title !== '' ? $this->title : null,
            'notes' => $this->notes !== '' ? $this->notes : null,
        ]);

        $this->photo->tags()->sync($this->resolveTagIds());
    }

    /**
     * @return array<int, int>
     */
    private function resolveTagIds(): array
    {
        return collect(explode(',', $this->tags))
            ->map(fn (string $name) => trim($name))
            ->filter()
            ->unique()
            ->map(fn (string $name) => Tag::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name],
            )->id)
            ->all();
    }
}
