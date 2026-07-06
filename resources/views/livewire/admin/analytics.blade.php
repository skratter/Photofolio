<div class="space-y-8">
    <flux:heading size="xl">Auswertung</flux:heading>

    <div class="flex gap-8">
        <flux:card class="flex-1">
            <flux:text>Startseite – Aufrufe</flux:text>
            <flux:heading size="xl">{{ $this->homepageViews['total'] }}</flux:heading>
        </flux:card>
        <flux:card class="flex-1">
            <flux:text>Startseite – Eindeutige Besucher</flux:text>
            <flux:heading size="xl">{{ $this->homepageViews['unique'] }}</flux:heading>
        </flux:card>
    </div>

    <div>
        <flux:heading size="lg" class="mb-4">Meistgesehene Alben</flux:heading>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>Titel</flux:table.column>
                <flux:table.column>Aufrufe</flux:table.column>
                <flux:table.column>Eindeutige Besucher</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->albums as $album)
                    <flux:table.row wire:key="album-{{ $album->id }}">
                        <flux:table.cell>
                            <flux:link href="{{ route('admin.albums.show', $album) }}" wire:navigate>
                                {{ $album->title }}
                            </flux:link>
                        </flux:table.cell>
                        <flux:table.cell>{{ $album->views_count }}</flux:table.cell>
                        <flux:table.cell class="text-zinc-500">{{ $album->unique_views_count }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="3" class="text-zinc-500">Noch keine Aufrufe erfasst.</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <div>
        <flux:heading size="lg" class="mb-4">Meistgesehene Fotos</flux:heading>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>Titel</flux:table.column>
                <flux:table.column>Album</flux:table.column>
                <flux:table.column>Aufrufe</flux:table.column>
                <flux:table.column>Eindeutige Besucher</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->photos as $photo)
                    <flux:table.row wire:key="photo-{{ $photo->id }}">
                        <flux:table.cell>
                            <flux:link href="{{ route('admin.albums.photos.show', [$photo->album, $photo]) }}" wire:navigate>
                                {{ $photo->title ?: $photo->original_filename }}
                            </flux:link>
                        </flux:table.cell>
                        <flux:table.cell class="text-zinc-500">{{ $photo->album->title }}</flux:table.cell>
                        <flux:table.cell>{{ $photo->views_count }}</flux:table.cell>
                        <flux:table.cell class="text-zinc-500">{{ $photo->unique_views_count }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4" class="text-zinc-500">Noch keine Aufrufe erfasst.</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <div>
        <flux:heading size="lg" class="mb-4">Meistgesehene Seiten</flux:heading>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>Titel</flux:table.column>
                <flux:table.column>Aufrufe</flux:table.column>
                <flux:table.column>Eindeutige Besucher</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->pages as $page)
                    <flux:table.row wire:key="page-{{ $page->id }}">
                        <flux:table.cell>{{ $page->title ?: $page->slug }}</flux:table.cell>
                        <flux:table.cell>{{ $page->views_count }}</flux:table.cell>
                        <flux:table.cell class="text-zinc-500">{{ $page->unique_views_count }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="3" class="text-zinc-500">Noch keine Aufrufe erfasst.</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</div>
