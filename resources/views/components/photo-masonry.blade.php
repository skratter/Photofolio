@props(['album', 'photos'])

@php
    $photosData = $photos->map(fn ($photo) => [
        'thumb' => route('albums.photos.thumb', [$album, $photo]),
        'display' => route('albums.photos.display', [$album, $photo]),
        'title' => $photo->title,
        'detailUrl' => Route::has('albums.photos.show') ? route('albums.photos.show', [$album, $photo]) : null,
    ])->values();
@endphp

<div x-data="photoMasonry(@js($photosData))" class="relative">
    <div class="columns-2 gap-4 sm:columns-3">
        @foreach ($photos as $index => $photo)
            <button type="button" x-on:click="open({{ $index }})"
                class="mb-4 block w-full break-inside-avoid" aria-label="{{ $photo->title ?: 'Foto ansehen' }}">
                <img src="{{ route('albums.photos.thumb', [$album, $photo]) }}" alt="{{ $photo->title }}"
                    loading="lazy" class="w-full rounded-lg">
            </button>
        @endforeach
    </div>

    <div x-show="index !== null" x-cloak x-on:keydown.escape.window="close()"
        x-on:keydown.right.window="next()" x-on:keydown.left.window="prev()"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4" x-on:click.self="close()">
        <button type="button" x-on:click="close()" class="absolute top-4 right-4 text-white/70 hover:text-white" aria-label="Schließen">
            <flux:icon name="x-mark" class="size-6" />
        </button>

        <button type="button" x-on:click="prev()" class="absolute left-4 text-white/70 hover:text-white" aria-label="Vorheriges Foto">
            <flux:icon name="chevron-left" class="size-8" />
        </button>

        <template x-if="index !== null">
            <img :src="photos[index].display" :alt="photos[index].title ?? ''" class="max-h-[85vh] max-w-full rounded-lg object-contain">
        </template>

        <button type="button" x-on:click="next()" class="absolute right-4 text-white/70 hover:text-white" aria-label="Nächstes Foto">
            <flux:icon name="chevron-right" class="size-8" />
        </button>

        <div class="absolute bottom-4 left-1/2 flex -translate-x-1/2 items-center gap-4 text-sm text-white/70">
            <span x-text="(index + 1) + ' / ' + photos.length"></span>
            <template x-if="index !== null && photos[index].detailUrl">
                <a :href="photos[index].detailUrl" class="underline hover:text-white">Details</a>
            </template>
        </div>
    </div>
</div>
