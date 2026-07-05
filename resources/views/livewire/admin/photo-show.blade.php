<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ $photo->title ?: $photo->original_filename }}</flux:heading>
        <flux:button variant="ghost" icon="arrow-left" href="{{ route('admin.albums.show', $album) }}" wire:navigate>
            Zurück zum Album
        </flux:button>
    </div>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
        <div class="self-start overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
            @if ($photo->isProcessed())
                <img src="{{ route('admin.photos.display', $photo) }}" class="w-full">
            @else
                <div class="flex aspect-square items-center justify-center">
                    <flux:icon name="arrow-path" class="size-8 animate-spin text-zinc-400" />
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <form wire:submit="save" class="space-y-6">
                <flux:field>
                    <flux:label>Titel</flux:label>
                    <flux:input wire:model="form.title" placeholder="{{ $photo->original_filename }}" />
                    <flux:error name="form.title" />
                </flux:field>

                <flux:field>
                    <flux:label>Notizen</flux:label>
                    <flux:textarea wire:model="form.notes" rows="3" />
                    <flux:error name="form.notes" />
                </flux:field>

                <flux:field>
                    <flux:label>Tags</flux:label>
                    <flux:input wire:model="form.tags" placeholder="Urlaub, Strand, Sonnenuntergang" />
                    <flux:description>Durch Kommas getrennt.</flux:description>
                    <flux:error name="form.tags" />
                </flux:field>

                <flux:button variant="primary" type="submit">Speichern</flux:button>
            </form>

            @if ($photo->isProcessed() || $photo->camera_make || $photo->camera_model || $photo->lens || $photo->taken_at || $photo->hasCoordinates())
                <div class="space-y-2 border-t border-zinc-200 pt-6 dark:border-zinc-700">
                    <flux:heading size="sm">Aufnahmedaten</flux:heading>
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                        @if ($photo->isProcessed())
                            <dt class="text-zinc-500">Abmessungen</dt>
                            <dd>{{ $photo->width }} × {{ $photo->height }} px</dd>
                        @endif
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
