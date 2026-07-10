<div class="mx-auto max-w-5xl px-6 pt-8 pb-16">
    <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ $album->title }}</flux:heading>
            @if ($album->description)
                <flux:text class="mt-2 text-zinc-500">{{ $album->description }}</flux:text>
            @endif
        </div>

        @if ($album->isAccessible() && $album->downloads_enabled && $this->totalPhotoCount > 0)
            <flux:button size="sm" icon="arrow-down-tray" :href="route('albums.download', $album)" download>
                Album herunterladen
            </flux:button>
        @endif
    </div>

    @if (! $album->isAccessible())
        <div class="mx-auto max-w-sm">
            <form wire:submit="unlock" class="space-y-4">
                <flux:field>
                    <flux:label>Dieses Album ist passwortgeschützt</flux:label>
                    <flux:input type="password" wire:model="password" autofocus />
                    <flux:error name="password" />
                </flux:field>
                <flux:button type="submit" variant="primary">Album öffnen</flux:button>
            </form>
        </div>
    @elseif ($this->photos->isEmpty())
        <flux:text class="text-zinc-500">Noch keine Fotos in diesem Album.</flux:text>
    @else
        {{-- wire:key ties the whole grid to the current page, so switching
             pages tears down and rebuilds the Alpine component fresh with
             that page's photos, instead of trying to patch its existing
             state in place (which is what the previous "load more" approach
             did via a hand-rolled event, and which never quite worked
             reliably). --}}
        <x-photo-masonry :album="$album" :photos="$this->photos" wire:key="masonry-page-{{ $this->page }}" />

        @if ($this->totalPages > 1)
            <div class="mt-10 flex items-center justify-center gap-2">
                <flux:button size="sm" wire:click="goToPage({{ $this->page - 1 }})" :disabled="$this->page <= 1">
                    « Zurück
                </flux:button>

                @for ($page = 1; $page <= $this->totalPages; $page++)
                    <flux:button size="sm" wire:click="goToPage({{ $page }})"
                        :variant="$page === $this->page ? 'primary' : 'ghost'">
                        {{ $page }}
                    </flux:button>
                @endfor

                <flux:button size="sm" wire:click="goToPage({{ $this->page + 1 }})" :disabled="$this->page >= $this->totalPages">
                    Weiter »
                </flux:button>
            </div>
        @endif
    @endif
</div>
