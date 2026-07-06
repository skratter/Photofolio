<?php

use App\Models\Page;
use App\Models\PageAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

test('guests cannot upload attachments', function () {
    $response = $this->post(route('admin.pages.attachments.store'), [
        'file' => UploadedFile::fake()->image('photo.jpg'),
    ]);

    $response->assertRedirect(route('login'));
});

test('it creates a draft page and attaches the image when no page_id is given', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('admin.pages.attachments.store'), [
        'file' => UploadedFile::fake()->image('photo.jpg'),
    ]);

    $response->assertOk()->assertJsonStructure(['page_id', 'url']);

    $page = Page::findOrFail($response->json('page_id'));

    expect($page->status)->toBe('draft')
        ->and($page->type)->toBe('standard')
        ->and(PageAttachment::where('page_id', $page->id)->where('kind', 'embedded_image')->exists())->toBeTrue();
});

test('it attaches the image to an existing page when page_id is given', function () {
    $user = User::factory()->create();
    $page = Page::factory()->create();

    $response = $this->actingAs($user)->post(route('admin.pages.attachments.store'), [
        'page_id' => $page->id,
        'file' => UploadedFile::fake()->image('photo.jpg'),
    ]);

    $response->assertOk()->assertJson(['page_id' => $page->id]);

    expect(Page::count())->toBe(1)
        ->and(PageAttachment::where('page_id', $page->id)->count())->toBe(1);
});

test('it rejects a file that is not an allowed image type', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('admin.pages.attachments.store'), [
        'file' => UploadedFile::fake()->create('malicious.exe', 100),
    ]);

    $response->assertSessionHasErrors('file');
    expect(PageAttachment::count())->toBe(0);
});

test('it rejects a file larger than 5 MB', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('admin.pages.attachments.store'), [
        'file' => UploadedFile::fake()->image('big.jpg')->size(5121),
    ]);

    $response->assertSessionHasErrors('file');
    expect(PageAttachment::count())->toBe(0);
});
