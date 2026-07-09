<div class="space-y-8">
    <flux:heading size="xl">Auswertung</flux:heading>

    <div class="flex gap-8">
        <flux:card class="flex-1">
            <flux:text>Startseite – Aufrufe</flux:text>
            <flux:heading size="xl">{{ $this->homepageViews['total'] }}</flux:heading>
        </flux:card>
        <flux:card class="flex-1">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <flux:text>Startseite – Eindeutige Besucher</flux:text>
                    <flux:heading size="xl">{{ $this->homepageViews['unique'] }}</flux:heading>
                </div>
                <flux:tooltip content="Verlauf anzeigen">
                    <flux:button icon="clock" size="sm" variant="ghost" wire:click="showHistory('page', {{ $this->homepageViews['id'] }})" />
                </flux:tooltip>
            </div>
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
                            <flux:link class="block max-w-xs truncate" href="{{ route('admin.albums.show', $album) }}" wire:navigate>
                                {{ $album->title }}
                            </flux:link>
                        </flux:table.cell>
                        <flux:table.cell>{{ $album->views_count }}</flux:table.cell>
                        <flux:table.cell class="text-zinc-500">{{ $album->unique_views_count }}</flux:table.cell>
                        <flux:table.cell class="text-right">
                            <flux:tooltip content="Verlauf anzeigen">
                                <flux:button icon="clock" size="sm" variant="ghost" wire:click="showHistory('album', {{ $album->id }})" />
                            </flux:tooltip>
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
                            <flux:link class="block max-w-xs truncate" href="{{ route('admin.albums.photos.show', [$photo->album, $photo]) }}" wire:navigate>
                                {{ $photo->title ?: $photo->original_filename }}
                            </flux:link>
                        </flux:table.cell>
                        <flux:table.cell class="text-zinc-500">
                            <span class="block max-w-32 truncate">{{ $photo->album->title }}</span>
                        </flux:table.cell>
                        <flux:table.cell>{{ $photo->views_count }}</flux:table.cell>
                        <flux:table.cell class="text-zinc-500">{{ $photo->unique_views_count }}</flux:table.cell>
                        <flux:table.cell class="text-right">
                            <flux:tooltip content="Verlauf anzeigen">
                                <flux:button icon="clock" size="sm" variant="ghost" wire:click="showHistory('photo', {{ $photo->id }})" />
                            </flux:tooltip>
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
                        <flux:table.cell>
                            <span class="block max-w-xs truncate">{{ $page->title ?: $page->slug }}</span>
                        </flux:table.cell>
                        <flux:table.cell>{{ $page->views_count }}</flux:table.cell>
                        <flux:table.cell class="text-zinc-500">{{ $page->unique_views_count }}</flux:table.cell>
                        <flux:table.cell class="text-right">
                            <flux:tooltip content="Verlauf anzeigen">
                                <flux:button icon="clock" size="sm" variant="ghost" wire:click="showHistory('page', {{ $page->id }})" />
                            </flux:tooltip>
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

    <div class="border-t border-zinc-200 pt-8 dark:border-zinc-700">
        <flux:heading size="lg" class="mb-1">Statistik zurücksetzen</flux:heading>
        <flux:text class="mb-4 text-zinc-500">
            Löscht aufgezeichnete Aufrufe unwiderruflich – für alle Alben, Fotos und Seiten zusammen.
        </flux:text>
        <div class="flex flex-wrap gap-2">
            <flux:button size="sm" variant="danger" wire:click="confirmReset('day')">Heute zurücksetzen</flux:button>
            <flux:button size="sm" variant="danger" wire:click="confirmReset('week')">Letzte 7 Tage zurücksetzen</flux:button>
            <flux:button size="sm" variant="danger" wire:click="confirmReset('month')">Letzte 30 Tage zurücksetzen</flux:button>
            <flux:button size="sm" variant="danger" wire:click="confirmReset('all')">Alles zurücksetzen</flux:button>
        </div>
    </div>

    <flux:modal wire:model="confirmingResetPeriod" name="confirm-reset" class="max-w-sm">
        <div class="space-y-4">
            <flux:heading size="lg">Statistik wirklich zurücksetzen?</flux:heading>
            <flux:text>
                @if ($confirmingResetPeriod === 'day')
                    Alle heute aufgezeichneten Aufrufe werden unwiderruflich gelöscht.
                @elseif ($confirmingResetPeriod === 'week')
                    Alle Aufrufe der letzten 7 Tage werden unwiderruflich gelöscht.
                @elseif ($confirmingResetPeriod === 'month')
                    Alle Aufrufe der letzten 30 Tage werden unwiderruflich gelöscht.
                @else
                    Die komplette Aufruf-Historie wird unwiderruflich gelöscht.
                @endif
            </flux:text>
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('confirmingResetPeriod', null)">Abbrechen</flux:button>
                <flux:button variant="danger" wire:click="resetStatistics">Zurücksetzen</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model="showHistoryModal" name="view-history" class="max-w-2xl">
        <div class="space-y-6">
            <flux:heading size="lg">Verlauf: {{ $this->historyLabel() }}</flux:heading>

            @if ($this->history)
                <div>
                    <div class="flex flex-wrap gap-x-10 gap-y-6">
                        <div class="min-w-44">
                            <flux:heading size="sm" class="mb-2 text-zinc-500">Letzte 7 Tage</flux:heading>
                            <ul class="divide-y divide-zinc-100 text-sm dark:divide-zinc-700">
                                @foreach ($this->history['daily'] as $row)
                                    <li class="flex items-center justify-between gap-6 py-1">
                                        <span class="text-zinc-500">{{ $row['label'] }}</span>
                                        <span class="tabular-nums">{{ $row['count'] }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        @if ($this->history['monthly']->isNotEmpty())
                            <div class="min-w-44">
                                <flux:heading size="sm" class="mb-2 text-zinc-500">Monatlich</flux:heading>
                                <ul class="divide-y divide-zinc-100 text-sm dark:divide-zinc-700">
                                    @foreach ($this->history['monthly'] as $row)
                                        <li class="flex items-center justify-between gap-6 py-1">
                                            <span class="text-zinc-500">{{ $row['label'] }}</span>
                                            <span class="tabular-nums">{{ $row['count'] }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if ($this->history['yearly']->isNotEmpty())
                            <div class="min-w-44">
                                <flux:heading size="sm" class="mb-2 text-zinc-500">Jährlich</flux:heading>
                                <ul class="divide-y divide-zinc-100 text-sm dark:divide-zinc-700">
                                    @foreach ($this->history['yearly'] as $row)
                                        <li class="flex items-center justify-between gap-6 py-1">
                                            <span class="text-zinc-500">{{ $row['label'] }}</span>
                                            <span class="tabular-nums">{{ $row['count'] }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>

                    <flux:text class="mt-4 block font-medium">Insgesamt: {{ $this->history['total'] }} Aufrufe</flux:text>
                </div>
            @endif

            @if ($this->origins)
                <div class="grid grid-cols-1 gap-8 border-t border-zinc-200 pt-6 sm:grid-cols-2 dark:border-zinc-700">
                    <div>
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <flux:heading size="sm" class="text-zinc-500">Herkunft (Referrer)</flux:heading>
                            @if ($this->origins['ownDomainsTotal'] > 0)
                                <flux:badge size="sm" color="amber">
                                    {{ $this->origins['ownDomainsTotal'] }}/{{ $this->origins['total'] }} eigene Domains
                                </flux:badge>
                            @endif
                        </div>
                        <ul class="divide-y divide-zinc-100 text-sm dark:divide-zinc-700">
                            @forelse ($this->origins['referrers'] as $row)
                                <li class="flex items-center justify-between gap-4 py-1.5">
                                    <span class="flex min-w-0 items-center gap-1.5">
                                        <span class="truncate {{ $row['isOwn'] ? 'font-medium text-amber-600 dark:text-amber-400' : 'text-zinc-500' }}">
                                            {{ $row['label'] }}
                                        </span>
                                    </span>
                                    <span class="tabular-nums">{{ $row['count'] }}</span>
                                </li>
                            @empty
                                <li class="py-1.5 text-zinc-500">Keine Daten.</li>
                            @endforelse
                        </ul>
                    </div>

                    <div>
                        <flux:heading size="sm" class="mb-2 text-zinc-500">User-Agents</flux:heading>
                        <ul class="divide-y divide-zinc-100 text-sm dark:divide-zinc-700">
                            @forelse ($this->origins['userAgents'] as $row)
                                <li class="flex items-center justify-between gap-4 py-1.5">
                                    <span class="min-w-0 truncate text-zinc-500" title="{{ $row['label'] }}">{{ $row['label'] }}</span>
                                    <span class="tabular-nums">{{ $row['count'] }}</span>
                                </li>
                            @empty
                                <li class="py-1.5 text-zinc-500">Keine Daten.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </flux:modal>
</div>
