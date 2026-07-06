<?php

use App\Livewire\Admin\PageManager;
use App\Models\Page;
use App\Models\PageAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('local');
});

test('guests are redirected to login', function () {
    $response = $this->get(route('admin.pages.index'));

    $response->assertRedirect(route('login'));
});

test('the page list shows title, slug and status', function () {
    $user = User::factory()->create();
    Page::factory()->create(['title' => 'Über mich', 'slug' => 'ueber-mich']);

    Livewire::actingAs($user)
        ->test(PageManager::class)
        ->assertSeeText('Über mich')
        ->assertSeeText('ueber-mich');
});

test('it creates a new standard page', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(PageManager::class)
        ->call('openCreateModal')
        ->set('form.title', 'FAQ')
        ->set('form.slug', 'faq')
        ->set('form.content', '<p>Fragen und Antworten.</p>')
        ->set('form.status', 'published')
        ->call('save')
        ->assertSet('showModal', false);

    $page = Page::where('slug', 'faq')->firstOrFail();

    expect($page->title)->toBe('FAQ')
        ->and($page->type)->toBe('standard')
        ->and($page->status)->toBe('published')
        ->and($page->published_at)->not->toBeNull();
});

test('editing a legal page updates content but keeps its type', function () {
    $user = User::factory()->create();
    $legal = Page::factory()->legal()->create(['title' => 'Impressum', 'slug' => 'impressum']);

    Livewire::actingAs($user)
        ->test(PageManager::class)
        ->call('openEditModal', $legal->id)
        ->set('form.content', '<p>Neuer rechtssicherer Text.</p>')
        ->call('save');

    expect($legal->fresh()->content)->toBe('<p>Neuer rechtssicherer Text.</p>')
        ->and($legal->fresh()->type)->toBe('legal');
});

test('it deletes a standard page', function () {
    $user = User::factory()->create();
    $page = Page::factory()->create();

    Livewire::actingAs($user)
        ->test(PageManager::class)
        ->call('confirmDelete', $page->id)
        ->call('delete')
        ->assertSet('confirmingDeleteId', null);

    $this->assertModelMissing($page);
});

test('it refuses to delete a legal page', function () {
    $user = User::factory()->create();
    $legal = Page::factory()->legal()->create();

    Livewire::actingAs($user)
        ->test(PageManager::class)
        ->call('confirmDelete', $legal->id)
        ->call('delete');

    $this->assertModelExists($legal);
});

test('attaching an image-created draft page keeps the already-typed title instead of the draft placeholder', function () {
    $user = User::factory()->create();

    // Simulates uploading an image before ever saving a brand new page: the
    // draft row already exists (with a placeholder title) by the time
    // attachExistingPage() runs, but the admin has already typed a real title.
    $draft = Page::factory()->create(['title' => 'Neue Seite', 'slug' => 'entwurf-xyz', 'status' => 'draft']);

    Livewire::actingAs($user)
        ->test(PageManager::class)
        ->call('openCreateModal')
        ->set('form.title', 'FAQ')
        ->set('form.slug', 'faq')
        ->call('attachExistingPage', $draft->id)
        ->assertSet('form.title', 'FAQ')
        ->assertSet('editingPageId', $draft->id)
        ->call('save');

    expect($draft->fresh()->title)->toBe('FAQ')
        ->and($draft->fresh()->slug)->toBe('faq')
        ->and(Page::count())->toBe(1);
});

test('creating a page with a slug that is already taken fails validation', function () {
    $user = User::factory()->create();
    Page::factory()->create(['slug' => 'faq']);

    Livewire::actingAs($user)
        ->test(PageManager::class)
        ->call('openCreateModal')
        ->set('form.title', 'FAQ 2')
        ->set('form.slug', 'faq')
        ->call('save')
        ->assertHasErrors(['form.slug' => 'unique']);
});

test('saving prunes embedded images that were removed from the content', function () {
    $user = User::factory()->create();
    $page = Page::factory()->create();
    Storage::disk('public')->put("pages/{$page->id}/kept.jpg", 'kept');
    Storage::disk('public')->put("pages/{$page->id}/removed.jpg", 'removed');

    $kept = PageAttachment::factory()->create(['page_id' => $page->id, 'path' => "pages/{$page->id}/kept.jpg"]);
    $removed = PageAttachment::factory()->create(['page_id' => $page->id, 'path' => "pages/{$page->id}/removed.jpg"]);
    $page->update(['content' => "<img src=\"{$kept->url()}\"><img src=\"{$removed->url()}\">"]);

    Livewire::actingAs($user)
        ->test(PageManager::class)
        ->call('openEditModal', $page->id)
        ->set('form.content', "<img src=\"{$kept->url()}\">")
        ->call('save');

    $this->assertModelExists($kept);
    $this->assertModelMissing($removed);
    Storage::disk('public')->assertExists("pages/{$page->id}/kept.jpg");
    Storage::disk('public')->assertMissing("pages/{$page->id}/removed.jpg");
});

test('closing the modal without saving deletes an opportunistically created draft', function () {
    $user = User::factory()->create();
    $draft = Page::factory()->create(['status' => 'draft']);

    Livewire::actingAs($user)
        ->test(PageManager::class)
        ->call('openCreateModal')
        ->call('attachExistingPage', $draft->id)
        ->set('showModal', false);

    $this->assertModelMissing($draft);
});

test('closing the modal after a successful save does not delete the page', function () {
    $user = User::factory()->create();
    $draft = Page::factory()->create(['status' => 'draft', 'slug' => 'entwurf-abc']);

    Livewire::actingAs($user)
        ->test(PageManager::class)
        ->call('openCreateModal')
        ->set('form.title', 'FAQ')
        ->set('form.slug', 'faq-3')
        ->call('attachExistingPage', $draft->id)
        ->call('save')
        ->set('showModal', false);

    $this->assertModelExists($draft);
});

test('closing the modal without ever attaching a draft does nothing', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(PageManager::class)
        ->call('openCreateModal')
        ->set('form.title', 'Untouched')
        ->set('showModal', false);

    expect(Page::where('title', 'Untouched')->exists())->toBeFalse();
});

test('uploading a download attaches it to the page being edited', function () {
    $user = User::factory()->create();
    $page = Page::factory()->create();

    Livewire::actingAs($user)
        ->test(PageManager::class)
        ->call('openEditModal', $page->id)
        ->set('newDownloads', [UploadedFile::fake()->create('handbuch.pdf', 100, 'application/pdf')])
        ->assertHasNoErrors();

    expect(PageAttachment::where('page_id', $page->id)->where('kind', 'download')->count())->toBe(1);
    expect(Storage::disk('local')->allFiles('livewire-tmp'))->toBeEmpty();
});

test('uploading a download for a brand new page creates a draft to attach it to', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(PageManager::class)
        ->call('openCreateModal')
        ->set('newDownloads', [UploadedFile::fake()->create('handbuch.pdf', 100, 'application/pdf')])
        ->assertSet('isLazyDraft', true);

    expect(PageAttachment::where('kind', 'download')->count())->toBe(1);
});

test('rejects a download file type that is not on the whitelist', function () {
    $user = User::factory()->create();
    $page = Page::factory()->create();

    Livewire::actingAs($user)
        ->test(PageManager::class)
        ->call('openEditModal', $page->id)
        ->set('newDownloads', [UploadedFile::fake()->create('script.exe', 100)])
        ->assertHasErrors(['newDownloads.0']);

    expect(PageAttachment::count())->toBe(0);
});

test('deleting a download attachment removes the file too', function () {
    $user = User::factory()->create();
    $page = Page::factory()->create();
    Storage::disk('public')->put("pages/{$page->id}/downloads/handbuch.pdf", 'bytes');
    $attachment = PageAttachment::factory()->download()->create([
        'page_id' => $page->id,
        'path' => "pages/{$page->id}/downloads/handbuch.pdf",
    ]);

    Livewire::actingAs($user)
        ->test(PageManager::class)
        ->call('openEditModal', $page->id)
        ->call('deleteAttachment', $attachment->id);

    $this->assertModelMissing($attachment);
    Storage::disk('public')->assertMissing("pages/{$page->id}/downloads/handbuch.pdf");
});

test('cannot delete an attachment belonging to a different page', function () {
    $user = User::factory()->create();
    $page = Page::factory()->create();
    $otherPage = Page::factory()->create();
    $attachment = PageAttachment::factory()->download()->create(['page_id' => $otherPage->id]);

    expect(fn () => Livewire::actingAs($user)
        ->test(PageManager::class)
        ->call('openEditModal', $page->id)
        ->call('deleteAttachment', $attachment->id)
    )->toThrow(ModelNotFoundException::class);

    $this->assertModelExists($attachment);
});
