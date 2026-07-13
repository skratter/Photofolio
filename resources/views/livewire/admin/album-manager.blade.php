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
            <flux:table.column>Fotos</flux:table.column>
            <flux:table.column>Sortierung</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->albums as $album)
                <flux:table.row wire:key="album-{{ $album->id }}">
                    <flux:table.cell>
                        <flux:link href="{{ route('admin.albums.show', $album) }}" wire:navigate>
                            {{ $album->title }}
                        </flux:link>
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
                    <flux:table.cell class="text-zinc-500">{{ $album->photos_count }}</flux:table.cell>
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
                <div
                    wire:key="description-editor-{{ $formInstance }}"
                    x-data
                    x-on:trix-change="$wire.set('form.description', $event.target.value, false)"
                >
                    <trix-toolbar id="album-description-toolbar-{{ $formInstance }}">
                        <div class="trix-button-row">
                            <span class="trix-button-group trix-button-group--text-tools" data-trix-button-group="text-tools">
                                <button type="button" class="trix-button trix-button--icon trix-button--icon-bold" data-trix-attribute="bold" data-trix-key="b" title="Fett" tabindex="-1">Fett</button>
                                <button type="button" class="trix-button trix-button--icon trix-button--icon-italic" data-trix-attribute="italic" data-trix-key="i" title="Kursiv" tabindex="-1">Kursiv</button>
                                <button type="button" class="trix-button trix-button--icon trix-button--icon-link" data-trix-attribute="href" data-trix-action="link" data-trix-key="k" title="Link" tabindex="-1">Link</button>
                            </span>

                            <span class="trix-button-group trix-button-group--block-tools" data-trix-button-group="block-tools">
                                <button type="button" class="trix-button trix-button--icon trix-button--icon-heading-1" data-trix-attribute="heading1" title="Überschrift" tabindex="-1">Überschrift</button>
                                <button type="button" class="trix-button trix-button--icon trix-button--icon-quote" data-trix-attribute="quote" title="Zitat" tabindex="-1">Zitat</button>
                                <button type="button" class="trix-button trix-button--icon trix-button--icon-bullet-list" data-trix-attribute="bullet" title="Aufzählung" tabindex="-1">Aufzählung</button>
                                <button type="button" class="trix-button trix-button--icon trix-button--icon-number-list" data-trix-attribute="number" title="Nummerierung" tabindex="-1">Nummerierung</button>
                            </span>

                            <span class="trix-button-group-spacer"></span>

                            <span class="trix-button-group trix-button-group--history-tools" data-trix-button-group="history-tools">
                                <button type="button" class="trix-button trix-button--icon trix-button--icon-undo" data-trix-action="undo" data-trix-key="z" title="Rückgängig" tabindex="-1">Rückgängig</button>
                                <button type="button" class="trix-button trix-button--icon trix-button--icon-redo" data-trix-action="redo" data-trix-key="shift+z" title="Wiederholen" tabindex="-1">Wiederholen</button>
                            </span>
                        </div>

                        <div class="trix-dialogs" data-trix-dialogs>
                            <div class="trix-dialog trix-dialog--link" data-trix-dialog="href" data-trix-dialog-attribute="href">
                                <div class="trix-dialog__link-fields">
                                    <input type="url" name="href" class="trix-input trix-input--dialog" placeholder="URL" aria-label="URL" data-trix-validate-href required data-trix-input>
                                    <div class="trix-button-group">
                                        <input type="button" class="trix-button trix-button--dialog" value="Link" data-trix-method="setAttribute">
                                        <input type="button" class="trix-button trix-button--dialog" value="Entfernen" data-trix-method="removeAttribute">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </trix-toolbar>

                    <input id="album-description-input-{{ $formInstance }}" type="hidden" value="{{ $form->description }}">
                    <trix-editor
                        toolbar="album-description-toolbar-{{ $formInstance }}"
                        input="album-description-input-{{ $formInstance }}"
                        class="trix-content block w-full rounded-lg border border-zinc-300 dark:border-zinc-700"
                    ></trix-editor>
                </div>
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

            @if ($form->visibility === 'public')
                <flux:field variant="inline">
                    <flux:checkbox wire:model="form.is_homepage" />
                    <flux:label>Als Startseite verwenden</flux:label>
                    <flux:description>Zeigt dieses Album statt des Platzhaltertexts auf der Startseite. Nur ein Album kann gleichzeitig als Startseite markiert sein - eine andere Markierung wird automatisch entfernt.</flux:description>
                </flux:field>
            @endif

            <flux:field variant="inline">
                <flux:checkbox wire:model="form.downloads_enabled" />
                <flux:label>Download erlauben</flux:label>
                <flux:description>Zeigt Besuchern einen Download-Button für einzelne Fotos und das ganze Album.</flux:description>
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