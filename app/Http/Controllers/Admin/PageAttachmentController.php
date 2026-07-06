<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PageAttachmentController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page_id' => ['nullable', 'integer', Rule::exists('pages', 'id')],
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        // A new page's images can be uploaded before the page itself has
        // ever been saved (the editor is open, but "Speichern" hasn't been
        // clicked yet) - create the draft row on first upload instead of
        // requiring the page to exist first.
        $page = isset($validated['page_id'])
            ? Page::whereKey($validated['page_id'])->firstOrFail()
            : Page::create([
                'title' => 'Neue Seite',
                'slug' => 'entwurf-'.Str::random(8),
                'type' => 'standard',
                'status' => 'draft',
            ]);

        $file = $request->file('file');
        $path = $file->store("pages/{$page->id}", 'public');

        abort_if($path === false, 500, 'Datei konnte nicht gespeichert werden.');

        PageAttachment::create([
            'page_id' => $page->id,
            'disk' => 'public',
            'path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'kind' => 'embedded_image',
            'size' => $file->getSize(),
        ]);

        return response()->json([
            'page_id' => $page->id,
            'url' => Storage::disk('public')->url($path),
        ]);
    }
}
