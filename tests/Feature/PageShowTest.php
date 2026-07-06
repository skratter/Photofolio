<?php

use App\Models\Page;
use App\Models\PageAttachment;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

test('it renders a published page', function () {
    $page = Page::factory()->create([
        'title' => 'Über mich',
        'slug' => 'ueber-mich',
        'content' => '<p>Hallo Welt.</p>',
        'status' => 'published',
    ]);

    $response = $this->get(route('pages.show', $page));

    $response->assertOk()
        ->assertSeeText('Über mich')
        ->assertSee('<p>Hallo Welt.</p>', false);
});

test('it renders a published legal page', function () {
    $legal = Page::factory()->legal()->create(['slug' => 'impressum', 'status' => 'published']);

    $response = $this->get(route('pages.show', $legal));

    $response->assertOk();
});

test('it returns 404 for a draft page', function () {
    $page = Page::factory()->create(['status' => 'draft']);

    $response = $this->get(route('pages.show', $page));

    $response->assertNotFound();
});

test('it returns 404 for an unknown slug', function () {
    $response = $this->get('/seite/does-not-exist');

    $response->assertNotFound();
});

test('it shows the meta description in the page head', function () {
    $page = Page::factory()->create(['status' => 'published', 'meta_description' => 'Eine tolle Beschreibung.']);

    $response = $this->get(route('pages.show', $page));

    $response->assertSee('Eine tolle Beschreibung.', false);
});

test('it lists downloadable attachments', function () {
    Storage::fake('public');
    $page = Page::factory()->create(['status' => 'published']);
    Storage::disk('public')->put("pages/{$page->id}/downloads/handbuch.pdf", 'bytes');
    PageAttachment::factory()->download()->create([
        'page_id' => $page->id,
        'path' => "pages/{$page->id}/downloads/handbuch.pdf",
        'original_filename' => 'handbuch.pdf',
    ]);

    $response = $this->get(route('pages.show', $page));

    $response->assertSeeText('handbuch.pdf');
});

test('it records a view for a guest visitor', function () {
    $page = Page::factory()->create(['status' => 'published']);

    $this->get(route('pages.show', $page));

    expect(views($page)->count())->toBe(1);
});

test('it does not record a view for an authenticated user', function () {
    $page = Page::factory()->create(['status' => 'published']);

    $this->actingAs(User::factory()->create())->get(route('pages.show', $page));

    expect(views($page)->count())->toBe(0);
});
