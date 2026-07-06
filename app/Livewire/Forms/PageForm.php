<?php

namespace App\Livewire\Forms;

use App\Models\Page;
use App\Models\PageAttachment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Form;

class PageForm extends Form
{
    public ?Page $page = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('required|string|max:255|alpha_dash')]
    public string $slug = '';

    #[Validate('nullable|string')]
    public string $content = '';

    #[Validate('nullable|string|max:255')]
    public string $meta_description = '';

    #[Validate('required|in:draft,published')]
    public string $status = 'draft';

    public bool $show_in_navigation = false;

    #[Validate('required|integer|min:0')]
    public int $sort_order = 0;

    public function setPage(Page $page): void
    {
        $this->page = $page;
        $this->title = $page->title ?? '';
        $this->slug = $page->slug;
        $this->content = $page->content ?? '';
        $this->meta_description = $page->meta_description ?? '';
        $this->status = $page->status;
        $this->show_in_navigation = $page->show_in_navigation;
        $this->sort_order = $page->sort_order;
    }

    public function generateSlugFromTitle(): void
    {
        // Only auto-fill on create; on edit the slug is already set and user-controlled,
        // overwriting it silently would break existing shared/indexed links.
        if ($this->page === null) {
            $this->slug = Str::slug($this->title);
        }
    }

    public function store(): Page
    {
        $this->validateSlugUniqueness();

        // New pages are always "standard" - "legal" is reserved for the
        // seeded Impressum/Datenschutz pages and isn't user-assignable.
        $page = Page::create([
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content ?: null,
            'meta_description' => $this->meta_description ?: null,
            'type' => 'standard',
            'status' => $this->status,
            'show_in_navigation' => $this->show_in_navigation,
            'sort_order' => $this->sort_order,
            'published_at' => $this->status === 'published' ? now() : null,
        ]);

        $this->pruneUnusedEmbeddedImages($page);

        return $page;
    }

    public function update(): Page
    {
        $this->validateSlugUniqueness();

        $this->page->update([
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content ?: null,
            'meta_description' => $this->meta_description ?: null,
            'status' => $this->status,
            'show_in_navigation' => $this->show_in_navigation,
            'sort_order' => $this->sort_order,
            'published_at' => $this->resolvePublishedAt(),
        ]);

        $this->pruneUnusedEmbeddedImages($this->page);

        return $this->page;
    }

    /**
     * Delete embedded-image attachments the admin inserted via the editor but
     * then removed from the text again before saving - Trix never tells the
     * server when that happens, so this is the only point where we can catch it.
     */
    private function pruneUnusedEmbeddedImages(Page $page): void
    {
        $page->attachments()
            ->where('kind', 'embedded_image')
            ->get()
            ->each(function (PageAttachment $attachment) {
                if (! str_contains($this->content, $attachment->url())) {
                    $attachment->delete();
                }
            });
    }

    private function resolvePublishedAt(): ?string
    {
        if ($this->status !== 'published') {
            return null;
        }

        // Keep the original publish date across edits - only stamp it the
        // first time a page actually goes live.
        return Carbon::parse($this->page->published_at ?? now())->toDateTimeString();
    }

    private function validateSlugUniqueness(): void
    {
        $this->validate([
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('pages', 'slug')->ignore($this->page?->id),
            ],
        ]);
    }
}
