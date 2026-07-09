{{-- resources/views/welcome.blade.php --}}
<x-layouts.public>
    @if ($homepageAlbum)
        <div class="mx-auto max-w-5xl px-6 pt-8 pb-16">
            <x-photo-masonry :album="$homepageAlbum" :photos="$homepagePhotos" />
        </div>
    @else
        <div class="flex items-center justify-center px-6 py-24">
            <div class="rounded-lg border-2 border-dashed border-neutral-500 px-12 py-16 text-center font-mono text-2xl text-neutral-500">
                soon&trade; | {{ config('app.name') }}
            </div>
        </div>
    @endif
</x-layouts.public>
