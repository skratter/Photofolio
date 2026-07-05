<div class="space-y-8">
    <flux:heading size="xl">Dashboard</flux:heading>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
        <flux:card>
            <flux:text>Alben</flux:text>
            <flux:heading size="xl">{{ $this->albumsCount }}</flux:heading>
        </flux:card>
        <flux:card>
            <flux:text>Fotos</flux:text>
            <flux:heading size="xl">{{ $this->photosCount }}</flux:heading>
        </flux:card>
        <flux:card>
            <flux:text>Startseite – Aufrufe</flux:text>
            <flux:heading size="xl">{{ $this->homepageViewsCount }}</flux:heading>
        </flux:card>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <flux:card class="overflow-hidden p-0">
            @if ($this->topAlbum)
                @php $coverPhoto = $this->topAlbum->effectiveCoverPhoto(); @endphp
                <a href="{{ route('admin.albums.show', $this->topAlbum) }}" wire:navigate class="block">
                    <div class="aspect-video bg-zinc-100 dark:bg-zinc-800">
                        @if ($coverPhoto && $coverPhoto->isProcessed())
                            <img
                                src="{{ route('admin.photos.thumb', $coverPhoto) }}"
                                loading="lazy"
                                class="h-full w-full object-cover"
                            >
                        @endif
                    </div>
                    <div class="p-4">
                        <flux:text>Meistgesehenes Album</flux:text>
                        <flux:heading size="lg">{{ $this->topAlbum->title }}</flux:heading>
                        <flux:text class="text-zinc-500">{{ $this->topAlbum->views_count }} Aufrufe</flux:text>
                    </div>
                </a>
            @else
                <div class="p-4">
                    <flux:text>Meistgesehenes Album</flux:text>
                    <flux:heading size="lg">–</flux:heading>
                </div>
            @endif
        </flux:card>

        <flux:card class="overflow-hidden p-0">
            @if ($this->topPhoto)
                <a href="{{ route('admin.albums.photos.show', [$this->topPhoto->album, $this->topPhoto]) }}" wire:navigate class="block">
                    <div class="aspect-video bg-zinc-100 dark:bg-zinc-800">
                        @if ($this->topPhoto->isProcessed())
                            <img
                                src="{{ route('admin.photos.thumb', $this->topPhoto) }}"
                                loading="lazy"
                                class="h-full w-full object-cover"
                            >
                        @endif
                    </div>
                    <div class="p-4">
                        <flux:text>Meistgesehenes Foto</flux:text>
                        <flux:heading size="lg">{{ $this->topPhoto->title ?: $this->topPhoto->original_filename }}</flux:heading>
                        <flux:text class="text-zinc-500">{{ $this->topPhoto->views_count }} Aufrufe</flux:text>
                    </div>
                </a>
            @else
                <div class="p-4">
                    <flux:text>Meistgesehenes Foto</flux:text>
                    <flux:heading size="lg">–</flux:heading>
                </div>
            @endif
        </flux:card>
    </div>
</div>
