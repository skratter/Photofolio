<div class="mx-auto max-w-5xl px-6 pt-8 pb-16">
    <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ $album->title }}</flux:heading>
            @if ($album->description)
                <flux:text class="mt-2 text-zinc-500">{{ $album->description }}</flux:text>
            @endif
        </div>

        @if ($album->isAccessible() && $album->downloads_enabled && $this->totalPhotoCount > 0 && ! $selecting)
            <flux:button size="sm" icon="arrow-down-tray" wire:click="startSelecting">
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
        @if ($selecting)
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-800/50">
                <div class="flex flex-wrap items-center gap-3">
                    <flux:text>{{ count($selectedPhotoIds) }} ausgewählt</flux:text>
                    <flux:button size="sm" variant="ghost" wire:click="selectAllOnPage">
                        Diese Seite auswählen
                    </flux:button>
                    @if (count($selectedPhotoIds) > 0)
                        <flux:button size="sm" variant="ghost" wire:click="clearSelection">
                            Auswahl aufheben
                        </flux:button>
                    @endif
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if (count($selectedPhotoIds) > 0)
                        <flux:button size="sm" variant="primary" icon="arrow-down-tray"
                            :href="route('albums.download', ['album' => $album, 'ids' => implode(',', $selectedPhotoIds)])" download>
                            Auswahl herunterladen ({{ count($selectedPhotoIds) }})
                        </flux:button>
                    @endif
                    <flux:button size="sm" icon="arrow-down-tray" :href="route('albums.download', $album)" download>
                        Alle herunterladen
                    </flux:button>
                    <flux:button size="sm" variant="ghost" wire:click="stopSelecting">
                        Fertig
                    </flux:button>
                </div>
            </div>

            {{-- A plain grid instead of x-photo-masonry: that component
                 positions tiles absolutely via JS (see layoutMasonry() in
                 app.js), reading each tile's inline style after Livewire
                 morphs the DOM. Since toggling a checkbox re-renders this
                 same page's tiles (unlike page changes, this doesn't get a
                 fresh wire:key), a morph could wipe those JS-applied styles
                 before anything re-triggers layoutMasonry(), collapsing the
                 grid. A plain, non-absolutely-positioned grid sidesteps that
                 entirely - same approach the admin photo grid already
                 relies on for its own checkbox selection. --}}
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                @foreach ($this->photos as $photo)
                    @php $isSelected = in_array($photo->id, $selectedPhotoIds); @endphp
                    <button type="button" wire:click="toggleSelect({{ $photo->id }})" wire:key="select-{{ $photo->id }}"
                        class="relative aspect-square cursor-pointer overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800"
                        aria-label="{{ $isSelected ? 'Foto abwählen' : 'Foto auswählen' }}" aria-pressed="{{ $isSelected ? 'true' : 'false' }}">
                        <img src="{{ route('albums.photos.thumb', [$album, $photo]) }}" alt="{{ $photo->title }}"
                            class="size-full object-cover {{ $isSelected ? 'opacity-70' : '' }}">
                        <div
                            class="pointer-events-none absolute inset-0 {{ $isSelected ? 'bg-blue-500/20 ring-4 ring-inset ring-blue-500' : '' }}">
                        </div>
                        <div
                            class="pointer-events-none absolute top-2 right-2 flex size-6 items-center justify-center rounded-full border-2 border-white {{ $isSelected ? 'bg-blue-500' : 'bg-black/40' }}">
                            @if ($isSelected)
                                <flux:icon name="check" class="size-4 text-white" />
                            @endif
                        </div>
                    </button>
                @endforeach
            </div>
        @else
            {{-- wire:key ties the whole grid to the current page, so switching
                 pages tears down and rebuilds the Alpine component fresh with
                 that page's photos, instead of trying to patch its existing
                 state in place (which is what the previous "load more" approach
                 did via a hand-rolled event, and which never quite worked
                 reliably). --}}
            <x-photo-masonry :album="$album" :photos="$this->photos" wire:key="masonry-page-{{ $this->page }}" />
        @endif

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
