<?php

use App\Models\Page;
use App\Models\PageAttachment;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

test('url returns the public disk url for its path', function () {
    $attachment = PageAttachment::factory()->create(['disk' => 'public', 'path' => 'pages/1/photo.jpg']);

    expect($attachment->url())->toBe(Storage::disk('public')->url('pages/1/photo.jpg'));
});

test('formattedSize renders bytes, kilobytes and megabytes', function () {
    expect(PageAttachment::factory()->make(['size' => 500])->formattedSize())->toBe('500 B')
        ->and(PageAttachment::factory()->make(['size' => 2048])->formattedSize())->toBe('2 KB')
        ->and(PageAttachment::factory()->make(['size' => 3 * 1024 * 1024])->formattedSize())->toBe('3 MB');
});

test('deleting an attachment removes its file from disk', function () {
    Storage::disk('public')->put('pages/1/photo.jpg', 'fake-bytes');
    $attachment = PageAttachment::factory()->create(['disk' => 'public', 'path' => 'pages/1/photo.jpg']);

    $attachment->delete();

    Storage::disk('public')->assertMissing('pages/1/photo.jpg');
});

test('deleting a page removes all of its attachment files too', function () {
    $page = Page::factory()->create();
    Storage::disk('public')->put('pages/'.$page->id.'/a.jpg', 'a');
    Storage::disk('public')->put('pages/'.$page->id.'/b.pdf', 'b');
    PageAttachment::factory()->create(['page_id' => $page->id, 'path' => 'pages/'.$page->id.'/a.jpg']);
    PageAttachment::factory()->download()->create(['page_id' => $page->id, 'path' => 'pages/'.$page->id.'/b.pdf']);

    $page->delete();

    Storage::disk('public')->assertMissing('pages/'.$page->id.'/a.jpg');
    Storage::disk('public')->assertMissing('pages/'.$page->id.'/b.pdf');
    expect(PageAttachment::count())->toBe(0);
});
