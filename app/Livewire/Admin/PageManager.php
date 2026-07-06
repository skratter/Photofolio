<?php

namespace App\Livewire\Admin;

use App\Livewire\Forms\PageForm;
use App\Models\Page;
use App\Models\PageAttachment;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.admin')]
class PageManager extends Component
{
    use WithFileUploads;

    public PageForm $form;

    public bool $showModal = false;

    public ?int $editingPageId = null;

    public ?int $confirmingDeleteId = null;

    /** @var array<int, TemporaryUploadedFile> */
    #[Validate(['newDownloads.*' => 'file|mimes:pdf,zip,doc,docx,xls,xlsx|max:10240'])]
    public array $newDownloads = [];

    // Bumped every time the modal opens so the Trix editor's wrapping element
    // gets a fresh wire:key - Trix only reads its starting content once when
    // the custom element is created, so reusing the same DOM node across two
    // "new page" attempts would leave the previous draft's text behind.
    public int $formInstance = 0;

    // True only while $editingPageId points at a page that was created
    // opportunistically by an upload during "create new page" (before the
    // admin ever clicked "Speichern"). Lets us tell a genuinely abandoned
    // draft apart from a real, already-saved page when the modal closes.
    public bool $isLazyDraft = false;

    /**
     * @return Collection<int, Page>
     */
    #[Computed]
    public function pages(): Collection
    {
        return Page::orderBy('sort_order')->orderBy('title')->get();
    }

    public function openCreateModal(): void
    {
        $this->form->reset();
        $this->editingPageId = null;
        $this->isLazyDraft = false;
        $this->formInstance++;
        $this->showModal = true;
    }

    public function openEditModal(int $pageId): void
    {
        $page = Page::findOrFail($pageId);
        $this->form->setPage($page);
        $this->editingPageId = $pageId;
        $this->isLazyDraft = false;
        $this->formInstance++;
        $this->showModal = true;
    }

    public function save(): void
    {
        if ($this->editingPageId === null) {
            $this->form->store();
        } else {
            $this->form->update();
        }

        // Reached "save" successfully - this is now a real, intentionally
        // kept page, not an abandoned draft, even if it started out as one.
        $this->isLazyDraft = false;
        $this->showModal = false;
        unset($this->pages);
    }

    /**
     * Called by the modal's wire:model whenever it closes, for any reason
     * (Abbrechen button, Escape key, backdrop click). If the page being
     * edited only exists because an upload created it opportunistically and
     * "Speichern" was never clicked, there's nothing worth keeping.
     */
    public function updatedShowModal(bool $value): void
    {
        if ($value === true || ! $this->isLazyDraft || $this->editingPageId === null) {
            return;
        }

        Page::find($this->editingPageId)?->delete();

        $this->editingPageId = null;
        $this->isLazyDraft = false;
        unset($this->pages);
    }

    public function attachExistingPage(int $pageId): void
    {
        // Fires after the first image upload on a brand new, not-yet-saved
        // page: the upload endpoint had to create the draft row so the
        // attachment has somewhere to belong to. Only the page reference is
        // updated here (not the full setPage() sync) so the title/slug/etc.
        // the admin already typed aren't clobbered by the draft's placeholder
        // values.
        if ($this->editingPageId === $pageId) {
            return;
        }

        $this->editingPageId = $pageId;
        $this->isLazyDraft = true;
        $this->form->page = Page::findOrFail($pageId);
    }

    /**
     * @return Collection<int, PageAttachment>
     */
    #[Computed]
    public function downloads(): Collection
    {
        if ($this->editingPageId === null) {
            return new Collection;
        }

        return PageAttachment::where('page_id', $this->editingPageId)
            ->where('kind', 'download')
            ->get();
    }

    public function updatedNewDownloads(): void
    {
        // Scoped to just this field - a bare $this->validate() would also
        // validate the (possibly still-empty) PageForm fields, e.g. rejecting
        // a download upload on a brand new page for having no title yet.
        $this->validate(['newDownloads.*' => 'file|mimes:pdf,zip,doc,docx,xls,xlsx|max:10240']);

        $page = $this->ensureDraftPageExists();

        foreach ($this->newDownloads as $file) {
            $this->storeDownload($file, $page);
        }

        $this->newDownloads = [];
        unset($this->downloads);
    }

    public function deleteAttachment(int $attachmentId): void
    {
        $attachment = PageAttachment::where('id', $attachmentId)
            ->where('page_id', $this->editingPageId)
            ->firstOrFail();

        $attachment->delete();
        unset($this->downloads);
    }

    private function ensureDraftPageExists(): Page
    {
        if ($this->editingPageId !== null) {
            return Page::findOrFail($this->editingPageId);
        }

        $page = Page::create([
            'title' => 'Neue Seite',
            'slug' => 'entwurf-'.Str::random(8),
            'type' => 'standard',
            'status' => 'draft',
        ]);

        $this->editingPageId = $page->id;
        $this->isLazyDraft = true;
        $this->form->page = $page;

        return $page;
    }

    private function storeDownload(TemporaryUploadedFile $file, Page $page): void
    {
        $extension = $file->getClientOriginalExtension();
        $filename = Str::random(40).".{$extension}";
        $path = "pages/{$page->id}/downloads/{$filename}";

        // storeAs() (not store()) because the file may be a Livewire test/fake
        // upload that was never registered as a real PHP upload - move_uploaded_file()
        // would reject it. storeAs() writes through the disk instead, then we
        // clean up the temp upload ourselves rather than waiting on Livewire's
        // automatic 24h sweep.
        $file->storeAs("pages/{$page->id}/downloads", $filename, ['disk' => 'public']);

        PageAttachment::create([
            'page_id' => $page->id,
            'disk' => 'public',
            'path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'kind' => 'download',
            'size' => $file->getSize(),
        ]);

        $file->delete();
    }

    public function confirmDelete(int $pageId): void
    {
        $this->confirmingDeleteId = $pageId;
    }

    public function delete(): void
    {
        $page = Page::findOrFail($this->confirmingDeleteId);

        abort_unless($page->isDeletable(), 403);

        $page->delete();
        $this->confirmingDeleteId = null;
        unset($this->pages);
    }

    public function render(): View
    {
        return view('livewire.admin.page-manager');
    }
}
