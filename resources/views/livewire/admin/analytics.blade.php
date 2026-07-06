<div class="space-y-8">
    <flux:heading size="xl">Auswertung</flux:heading>

    <div class="flex items-stretch gap-8">
        <flux:card class="flex-1">
            <flux:text>Startseite – Aufrufe</flux:text>
            <flux:heading size="xl">{{ $this->homepageViews['total'] }}</flux:heading>
        </flux:card>
        <flux:card class="flex-1">
            <flux:text>Startseite – Eindeutige Besucher</flux:text>
            <flux:heading size="xl">{{ $this->homepageViews['unique'] }}</flux:heading>
        </flux:card>
        <flux:card class="flex flex-1 items-center justify-center">
            <flux:button variant="ghost" wire:click="showHistory('page', {{ $this->homepageViews['id'] }})">
                Verlauf
            </flux:button>
        </flux:card>
    </div>

    <div>
        <flux:heading size="lg" class="mb-4">Meistgesehene Alben</flux:heading>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>Titel</flux:table.column>
                <flux:table.column>Aufrufe</flux:table.column>
                <flux:table.column>Eindeutige Besucher</flux:table.column>
                <flux:table.column></flux:table.column>
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
                        <flux:table.cell>
                            <flux:button size="sm" variant="ghost" wire:click="showHistory('album', {{ $album->id }})">
                                Verlauf
                            </flux:button>
                        </flux:table.cell>
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
        <flux:heading size="lg" class="mb-4">Meistgesehene Fotos</flux:heading>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>Titel</flux:table.column>
                <flux:table.column>Album</flux:table.column>
                <flux:table.column>Aufrufe</flux:table.column>
                <flux:table.column>Eindeutige Besucher</flux:table.column>
                <flux:table.column></flux:table.column>
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
                        <flux:table.cell>
                            <flux:button size="sm" variant="ghost" wire:click="showHistory('photo', {{ $photo->id }})">
                                Verlauf
                            </flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-zinc-500">Noch keine Aufrufe erfasst.</flux:table.cell>
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
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->pages as $page)
                    <flux:table.row wire:key="page-{{ $page->id }}">
                        <flux:table.cell>{{ $page->title ?: $page->slug }}</flux:table.cell>
                        <flux:table.cell>{{ $page->views_count }}</flux:table.cell>
                        <flux:table.cell class="text-zinc-500">{{ $page->unique_views_count }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:button size="sm" variant="ghost" wire:click="showHistory('page', {{ $page->id }})">
                                Verlauf
                            </flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4" class="text-zinc-500">Noch keine Aufrufe erfasst.</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal wire:model="showHistoryModal" name="view-history" class="max-w-2xl">
        <div class="space-y-6">
            <flux:heading size="lg">Verlauf: {{ $this->historyLabel() }}</flux:heading>

            @if ($this->history)
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                    <div>
                        <flux:heading size="sm" class="mb-2">Letzte 7 Tage</flux:heading>
                        <ul class="space-y-1 text-sm">
                            @foreach ($this->history['daily'] as $row)
                                <li class="flex justify-between gap-4">
                                    <span class="text-zinc-500">{{ $row['label'] }}</span>
                                    <span>{{ $row['count'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div>
                        <flux:heading size="sm" class="mb-2">Monatlich</flux:heading>
                        <ul class="space-y-1 text-sm">
                            @forelse ($this->history['monthly'] as $row)
                                <li class="flex justify-between gap-4">
                                    <span class="text-zinc-500">{{ $row['label'] }}</span>
                                    <span>{{ $row['count'] }}</span>
                                </li>
                            @empty
                                <li class="text-zinc-500">Keine Daten.</li>
                            @endforelse
                        </ul>
                    </div>

                    <div>
                        <flux:heading size="sm" class="mb-2">Jährlich</flux:heading>
                        <ul class="space-y-1 text-sm">
                            @forelse ($this->history['yearly'] as $row)
                                <li class="flex justify-between gap-4">
                                    <span class="text-zinc-500">{{ $row['label'] }}</span>
                                    <span>{{ $row['count'] }}</span>
                                </li>
                            @empty
                                <li class="text-zinc-500">Keine Daten.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>

                <flux:text class="font-medium">Insgesamt: {{ $this->history['total'] }} Aufrufe</flux:text>
            @endif

            @if ($this->origins)
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 border-t border-zinc-200 pt-6 dark:border-zinc-700">
                    <div>
                        <flux:heading size="sm" class="mb-2">Herkunft (Referrer)</flux:heading>
                        <ul class="space-y-1 text-sm">
                            @forelse ($this->origins['referrers'] as $row)
                                <li class="flex justify-between gap-4">
                                    <span class="text-zinc-500 truncate">{{ $row['label'] }}</span>
                                    <span>{{ $row['count'] }}</span>
                                </li>
                            @empty
                                <li class="text-zinc-500">Keine Daten.</li>
                            @endforelse
                        </ul>
                    </div>

                    <div>
                        <flux:heading size="sm" class="mb-2">User-Agents</flux:heading>
                        <ul class="space-y-1 text-sm">
                            @forelse ($this->origins['userAgents'] as $row)
                                <li class="flex justify-between gap-4">
                                    <span class="text-zinc-500 truncate" title="{{ $row['label'] }}">{{ $row['label'] }}</span>
                                    <span>{{ $row['count'] }}</span>
                                </li>
                            @empty
                                <li class="text-zinc-500">Keine Daten.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </flux:modal>
</div>
