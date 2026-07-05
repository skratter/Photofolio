<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ $album->title }}</flux:heading>
            <flux:text class="text-zinc-500">{{ $album->photos()->count() }} Fotos</flux:text>
        </div>
        <flux:button variant="ghost" icon="arrow-left" href="{{ route('admin.albums.index') }}" wire:navigate>
            Zurück zu Alben
        </flux:button>
    </div>

    <livewire:admin.photo-uploader :album="$album" />

    @if ($this->photos->isEmpty())
        <flux:text class="text-center text-zinc-400 py-12">
            Noch keine Fotos in diesem Album.
        </flux:text>
    @else
        <div class="flex flex-wrap items-center justify-between gap-3">
            <flux:button size="sm" variant="ghost" wire:click="toggleSelectAll">
                {{ count($selectedPhotoIds) === $this->photos->count() ? 'Auswahl aufheben' : 'Alle auswählen' }}
            </flux:button>

            @if (count($selectedPhotoIds) > 0)
                <div class="flex flex-wrap items-center gap-2">
                    <flux:text class="text-zinc-500">{{ count($selectedPhotoIds) }} ausgewählt</flux:text>
                    <flux:button size="sm" icon="arrow-down-tray" wire:click="download" wire:target="download">
                        {{ count($selectedPhotoIds) > 1 ? 'Als ZIP herunterladen' : 'Herunterladen' }}
                    </flux:button>
                    <flux:button size="sm" icon="pencil" wire:click="openBulkRenameModal">
                        Umbenennen
                    </flux:button>
                    <flux:button
                        size="sm"
                        icon="trash"
                        variant="danger"
                        wire:click="bulkDelete"
                        wire:confirm="{{ count($selectedPhotoIds) }} Fotos wirklich löschen?"
                    >
                        Löschen
                    </flux:button>
                </div>
            @endif
        </div>

        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
            @foreach ($this->photos as $photo)
                <div
                    wire:key="photo-{{ $photo->id }}"
                    @class([
                        'group relative aspect-square overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800',
                        'ring-2 ring-amber-400' => $album->cover_photo_id === $photo->id,
                    ])
                >
                    <a href="{{ route('admin.albums.photos.show', [$album, $photo]) }}" wire:navigate class="absolute inset-0">
                        @if ($photo->isProcessed())
                            <img
                                src="{{ route('admin.photos.thumb', $photo) }}"
                                loading="lazy"
                                class="h-full w-full object-cover"
                            >
                        @else
                            <div class="flex h-full w-full items-center justify-center">
                                <flux:icon name="arrow-path" class="size-6 animate-spin text-zinc-400" />
                            </div>
                        @endif
                    </a>

                    <label class="absolute left-1 top-1 z-10 flex size-7 items-center justify-center rounded-full bg-black/60">
                        <flux:checkbox wire:model.live="selectedPhotoIds" value="{{ $photo->id }}" />
                    </label>

                    <div class="absolute right-1 top-1 z-10 flex gap-1">
                        <button
                            type="button"
                            wire:click="setCoverPhoto({{ $photo->id }})"
                            title="Als Titelbild festlegen"
                            @class([
                                'flex size-7 items-center justify-center rounded-full bg-black/60 transition-opacity',
                                'text-amber-400' => $album->cover_photo_id === $photo->id,
                                'text-white opacity-0 group-hover:opacity-100' => $album->cover_photo_id !== $photo->id,
                            ])
                        >
                            <flux:icon name="star" variant="{{ $album->cover_photo_id === $photo->id ? 'solid' : 'outline' }}" class="size-4" />
                        </button>

                        <button
                            type="button"
                            wire:click="deletePhoto({{ $photo->id }})"
                            wire:confirm="Foto wirklich löschen?"
                            class="flex size-7 items-center justify-center rounded-full bg-black/60 text-white opacity-0 transition-opacity group-hover:opacity-100"
                        >
                            <flux:icon name="trash" class="size-4" />
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <flux:modal wire:model="showBulkRenameModal" name="bulk-rename" class="max-w-sm">
        <form wire:submit="bulkRename" class="space-y-6">
            <flux:heading size="lg">{{ count($selectedPhotoIds) }} Fotos umbenennen</flux:heading>

            <flux:field>
                <flux:label>Basisname</flux:label>
                <flux:input wire:model="bulkRenameBaseName" placeholder="Urlaub" />
                <flux:description>Ergibt „Urlaub 1“, „Urlaub 2“, ... in aktueller Sortierreihenfolge.</flux:description>
                <flux:error name="bulkRenameBaseName" />
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" type="button" wire:click="$set('showBulkRenameModal', false)">
                    Abbrechen
                </flux:button>
                <flux:button variant="primary" type="submit">
                    Umbenennen
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
