<div class="mx-auto max-w-5xl px-6 pt-8 pb-16">
    <flux:link href="{{ route('albums.show', $album->slug) }}" wire:navigate icon="arrow-left" class="mb-6 inline-flex">
        {{ $album->title }}
    </flux:link>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
        <div class="relative self-start overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
            <img src="{{ route('albums.photos.display', [$album, $photo]) }}" alt="{{ $photo->title }}" class="w-full">

            @if ($this->previousPhoto)
                <flux:link href="{{ route('albums.photos.show', [$album, $this->previousPhoto]) }}" wire:navigate
                    class="absolute top-1/2 left-2 -translate-y-1/2 rounded-full bg-black/50 p-2 text-white hover:bg-black/70"
                    aria-label="Vorheriges Foto">
                    <flux:icon name="chevron-left" class="size-5" />
                </flux:link>
            @endif

            @if ($this->nextPhoto)
                <flux:link href="{{ route('albums.photos.show', [$album, $this->nextPhoto]) }}" wire:navigate
                    class="absolute top-1/2 right-2 -translate-y-1/2 rounded-full bg-black/50 p-2 text-white hover:bg-black/70"
                    aria-label="Nächstes Foto">
                    <flux:icon name="chevron-right" class="size-5" />
                </flux:link>
            @endif
        </div>

        <div class="space-y-6">
            <div class="flex items-start justify-between gap-4">
                <flux:heading size="xl">{{ $photo->title ?: 'Ohne Titel' }}</flux:heading>

                @if ($album->downloads_enabled)
                    <flux:button size="sm" icon="arrow-down-tray" :href="route('albums.photos.download', [$album, $photo])" download>
                        Herunterladen
                    </flux:button>
                @endif
            </div>

            @if ($photo->notes)
                <flux:text class="text-zinc-500">{{ $photo->notes }}</flux:text>
            @endif

            @if ($photo->camera_make || $photo->camera_model || $photo->lens || $photo->taken_at || $photo->hasCoordinates())
                <div class="space-y-2 border-t border-zinc-200 pt-6 dark:border-zinc-700">
                    <flux:heading size="sm">Aufnahmedaten</flux:heading>
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                        <dt class="text-zinc-500">Abmessungen</dt>
                        <dd>{{ $photo->width }} × {{ $photo->height }} px</dd>

                        @if ($photo->camera_make || $photo->camera_model)
                            <dt class="text-zinc-500">Kamera</dt>
                            <dd>{{ trim("{$photo->camera_make} {$photo->camera_model}") }}</dd>
                        @endif
                        @if ($photo->lens)
                            <dt class="text-zinc-500">Objektiv</dt>
                            <dd>{{ $photo->lens }}</dd>
                        @endif
                        @if ($photo->aperture)
                            <dt class="text-zinc-500">Blende</dt>
                            <dd>{{ $photo->aperture }}</dd>
                        @endif
                        @if ($photo->shutter_speed)
                            <dt class="text-zinc-500">Belichtungszeit</dt>
                            <dd>{{ $photo->shutter_speed }}</dd>
                        @endif
                        @if ($photo->iso)
                            <dt class="text-zinc-500">ISO</dt>
                            <dd>{{ $photo->iso }}</dd>
                        @endif
                        @if ($photo->focal_length)
                            <dt class="text-zinc-500">Brennweite</dt>
                            <dd>{{ $photo->focal_length }}</dd>
                        @endif
                        @if ($photo->taken_at)
                            <dt class="text-zinc-500">Aufgenommen</dt>
                            <dd>{{ $photo->taken_at->format('d.m.Y H:i') }}</dd>
                        @endif
                        @if ($photo->hasCoordinates())
                            <dt class="text-zinc-500">Standort</dt>
                            <dd>{{ $photo->latitude }}, {{ $photo->longitude }}</dd>
                        @endif
                    </dl>
                </div>
            @endif
        </div>
    </div>
</div>
