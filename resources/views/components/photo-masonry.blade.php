@props(['album', 'photos', 'pool' => null, 'rotateSeconds' => null])

@php
    $mapPhoto = fn ($photo) => [
        'thumb' => route('albums.photos.thumb', [$album, $photo]),
        'display' => route('albums.photos.display', [$album, $photo]),
        'title' => $photo->title,
        'width' => $photo->width,
        'height' => $photo->height,
        'detailUrl' => Route::has('albums.photos.show') ? route('albums.photos.show', [$album, $photo]) : null,
    ];

    $photosData = $photos->map($mapPhoto)->values();
    $poolData = ($pool ?? collect())->map($mapPhoto)->values();
    $settings = \App\Models\Setting::current();
@endphp

<div {{ $attributes }}
    x-data="photoMasonry(@js($photosData), @js($poolData), {{ $rotateSeconds ?? 'null' }}, {{ $settings->slideshow_autoplay_seconds }}, {{ $settings->masonry_columns }})"
    x-on:resize.window.debounce.200ms="layoutMasonry()" class="relative">
    {{-- CSS columns/waterfall always leaves a ragged bottom edge, since it
         fills one column fully before starting the next. This computes a
         real shortest-column-first masonry layout instead - from the known
         width/height (see layoutMasonry() in app.js), so columns end up
         evenly balanced and the grid finishes flush. Items are positioned
         absolutely by JS; data-slot lets both this and the rotation feature
         find a specific grid position.

         x-cloak matters here specifically: the browser starts fetching and
         painting <img> tags as soon as it parses them, well before Alpine's
         deferred script has even run - so without it, images briefly show
         at their raw, un-positioned default spot (effectively all stacked
         at the top-left) until layoutMasonry() gets a chance to run. x-cloak
         (backed by the "[x-cloak] { display: none }" rule already shipped
         by Livewire) hides the element from the very first paint, and
         Alpine removes it as part of the same init pass that evaluates the
         opacity binding below, so there's no gap between the two. --}}
    <div x-ref="grid" x-cloak x-bind:class="laidOut ? 'opacity-100' : 'opacity-0'" class="relative transition-opacity duration-300">
        @foreach ($photos as $index => $photo)
            <button type="button" x-on:click="open({{ $index }})" x-data="{ loaded: false }" data-slot="{{ $index }}"
                class="absolute overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800 transition-[top,left,width,height] duration-500 ease-in-out"
                aria-label="{{ $photo->title ?: 'Foto ansehen' }}">
                {{-- No loading="lazy": these tiles are positioned via
                     JS (see layoutMasonry() in app.js), so at the moment the
                     browser decides whether an image is "near enough" to
                     load, every tile is still stacked at its default
                     pre-layout position, not its real one. Images the
                     browser wrongly guesses are far away then never load at
                     all, even once moved into view - moving an element via
                     JS doesn't reliably re-trigger the lazy-load check. --}}
                {{-- x-init covers cached images the browser may finish
                     loading (and fire "load" for) before Alpine has even
                     attached the x-on:load listener below - without this,
                     that event fires into nothing and the tile stays
                     invisible forever despite the image being right there. --}}
                <img src="{{ route('albums.photos.thumb', [$album, $photo]) }}" alt="{{ $photo->title }}"
                    x-init="if ($el.complete) loaded = true" x-on:load="loaded = true"
                    x-bind:class="loaded ? 'opacity-100' : 'opacity-0'"
                    class="h-full w-full rounded-lg object-cover transition-opacity duration-700">
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
            <button type="button" x-on:click="toggleAutoplay()" class="flex items-center gap-1 hover:text-white">
                <flux:icon x-show="!isAutoplaying" name="play" class="size-4" />
                <flux:icon x-show="isAutoplaying" name="pause" class="size-4" />
                <span x-text="isAutoplaying ? 'Pause' : 'Autoplay'"></span>
            </button>
            <template x-if="index !== null && photos[index].detailUrl">
                <a :href="photos[index].detailUrl" class="underline hover:text-white">Details</a>
            </template>
        </div>
    </div>
</div>
