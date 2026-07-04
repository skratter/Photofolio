<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Alben</flux:heading>
        <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
            Neues Album
        </flux:button>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Titel</flux:table.column>
            <flux:table.column>Slug</flux:table.column>
            <flux:table.column>Sichtbarkeit</flux:table.column>
            <flux:table.column>Sortierung</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->albums as $album)
                <flux:table.row wire:key="album-{{ $album->id }}">
                    <flux:table.cell>
                        {{ $album->title }}
                        @if ($album->is_homepage)
                            <flux:badge size="sm" color="blue">Startseite</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="text-zinc-500">{{ $album->slug }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($album->visibility === 'private')
                            <flux:badge color="amber" icon="lock-closed">Privat</flux:badge>
                        @else
                            <flux:badge color="green" icon="globe-alt">Öffentlich</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>{{ $album->sort_order }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex gap-2 justify-end">
                            <flux:button size="sm" icon="pencil" wire:click="openEditModal({{ $album->id }})" />
                            <flux:button size="sm" icon="trash" variant="danger" wire:click="confirmDelete({{ $album->id }})" />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    {{-- Create/Edit Modal --}}
    <flux:modal wire:model="showModal" name="album-form" class="max-w-lg">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">
                {{ $editingAlbumId === null ? 'Neues Album' : 'Album bearbeiten' }}
            </flux:heading>

            <flux:field>
                <flux:label>Titel</flux:label>
                <flux:input wire:model.live.debounce.300ms="form.title" wire:change="form.generateSlugFromTitle" />
                <flux:error name="form.title" />
            </flux:field>

            <flux:field>
                <flux:label>Slug</flux:label>
                <flux:input wire:model="form.slug" />
                <flux:error name="form.slug" />
            </flux:field>

            <flux:field>
                <flux:label>Beschreibung</flux:label>
                <flux:textarea wire:model="form.description" rows="3" />
                <flux:error name="form.description" />
            </flux:field>

            <flux:field>
                <flux:label>Sichtbarkeit</flux:label>
                <flux:select wire:model.live="form.visibility">
                    <flux:select.option value="public">Öffentlich</flux:select.option>
                    <flux:select.option value="private">Privat (passwortgeschützt)</flux:select.option>
                </flux:select>
                <flux:error name="form.visibility" />
            </flux:field>

            @if ($form->visibility === 'private')
                <flux:field>
                    <flux:label>
                        Passwort
                        @if ($editingAlbumId !== null)
                            <span class="text-zinc-400 font-normal">(leer lassen, um bestehendes Passwort zu behalten)</span>
                        @endif
                    </flux:label>
                    <flux:input type="password" wire:model="form.password" />
                    <flux:error name="form.password" />
                </flux:field>
            @endif

            <flux:field>
                <flux:label>Sortierung</flux:label>
                <flux:input type="number" wire:model="form.sort_order" />
                <flux:error name="form.sort_order" />
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" type="button" wire:click="$set('showModal', false)">
                    Abbrechen
                </flux:button>
                <flux:button variant="primary" type="submit">
                    Speichern
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Delete Confirmation --}}
    <flux:modal wire:model="confirmingDeleteId" name="confirm-delete" class="max-w-sm">
        <div class="space-y-4">
            <flux:heading size="lg">Album löschen?</flux:heading>
            <flux:text>
                Das Album und alle enthaltenen Fotos werden unwiderruflich gelöscht.
            </flux:text>
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('confirmingDeleteId', null)">
                    Abbrechen
                </flux:button>
                <flux:button variant="danger" wire:click="delete">
                    Löschen
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>