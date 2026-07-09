<div class="mx-auto max-w-5xl px-6 pt-8 pb-16">
    <flux:heading size="xl" class="mb-12">Alben</flux:heading>

    @if ($this->albums->isEmpty())
        <flux:text class="text-zinc-500">Noch keine Alben vorhanden.</flux:text>
    @else
        <div class="grid grid-cols-2 gap-x-6 gap-y-10 sm:grid-cols-3">
            @foreach ($this->albums as $album)
                <a href="{{ Route::has('albums.show') ? route('albums.show', $album->slug) : '#' }}" wire:navigate class="group block">
                    <div class="aspect-square overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
                        @php $cover = $album->effectiveCoverPhoto(); @endphp
                        @if ($cover?->isProcessed())
                            <img src="{{ route('albums.photos.thumb', [$album, $cover]) }}" alt=""
                                class="size-full object-cover transition duration-300 group-hover:scale-105">
                        @endif
                    </div>
                    <div class="mt-2">
                        <flux:heading size="sm">{{ $album->title }}</flux:heading>
                        <flux:text size="sm" class="text-zinc-500">
                            {{ $album->photos_count }} {{ $album->photos_count === 1 ? 'Foto' : 'Fotos' }}
                        </flux:text>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
