<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Seiten</flux:heading>
        <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
            Neue Seite
        </flux:button>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Titel</flux:table.column>
            <flux:table.column>Slug</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Navigation</flux:table.column>
            <flux:table.column>Sortierung</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->pages as $page)
                <flux:table.row wire:key="page-{{ $page->id }}">
                    <flux:table.cell>
                        {{ $page->title }}
                        @if ($page->type === 'legal')
                            <flux:badge size="sm" color="amber">Rechtlich erforderlich</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="text-zinc-500">{{ $page->slug }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($page->status === 'published')
                            <flux:badge color="green" icon="check-circle">Veröffentlicht</flux:badge>
                        @else
                            <flux:badge color="zinc" icon="pencil">Entwurf</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="text-zinc-500">
                        {{ $page->show_in_navigation ? 'Ja' : 'Nein' }}
                    </flux:table.cell>
                    <flux:table.cell>{{ $page->sort_order }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex gap-2 justify-end">
                            <flux:button size="sm" icon="pencil" wire:click="openEditModal({{ $page->id }})" />

                            @if ($page->isDeletable())
                                <flux:button size="sm" icon="trash" variant="danger" wire:click="confirmDelete({{ $page->id }})" />
                            @else
                                <flux:tooltip content="Rechtlich erforderliche Seite kann nicht gelöscht werden">
                                    <div>
                                        <flux:button size="sm" icon="trash" variant="danger" disabled />
                                    </div>
                                </flux:tooltip>
                            @endif
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    {{-- Create/Edit Modal --}}
    <flux:modal wire:model="showModal" name="page-form" class="max-w-3xl">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">
                {{ $editingPageId === null ? 'Neue Seite' : 'Seite bearbeiten' }}
            </flux:heading>

            <flux:field>
                <flux:label>Titel</flux:label>
                <flux:input wire:model.live.debounce.300ms="form.title" />
                <flux:error name="form.title" />
            </flux:field>

            <flux:field>
                <flux:label>Slug</flux:label>
                <flux:input wire:model="form.slug" />
                <flux:error name="form.slug" />
            </flux:field>

            <flux:field>
                <flux:label>Inhalt</flux:label>
                <div
                    wire:key="content-editor-{{ $formInstance }}"
                    x-data
                    x-on:trix-change="$wire.set('form.content', $event.target.value, false)"
                >
                    <trix-toolbar id="page-content-toolbar-{{ $formInstance }}">
                        <div class="trix-button-row">
                            <span class="trix-button-group trix-button-group--text-tools" data-trix-button-group="text-tools">
                                <button type="button" class="trix-button trix-button--icon trix-button--icon-bold" data-trix-attribute="bold" data-trix-key="b" title="Fett" tabindex="-1">Fett</button>
                                <button type="button" class="trix-button trix-button--icon trix-button--icon-italic" data-trix-attribute="italic" data-trix-key="i" title="Kursiv" tabindex="-1">Kursiv</button>
                                <button type="button" class="trix-button trix-button--icon trix-button--icon-link" data-trix-attribute="href" data-trix-action="link" data-trix-key="k" title="Link" tabindex="-1">Link</button>
                            </span>

                            <span class="trix-button-group trix-button-group--block-tools" data-trix-button-group="block-tools">
                                <button type="button" class="trix-button trix-button--icon trix-button--icon-heading-1" data-trix-attribute="heading1" title="Überschrift" tabindex="-1">Überschrift</button>
                                <button type="button" class="trix-button trix-button--icon trix-button--icon-quote" data-trix-attribute="quote" title="Zitat" tabindex="-1">Zitat</button>
                                <button type="button" class="trix-button trix-button--icon trix-button--icon-code" data-trix-attribute="code" title="Code" tabindex="-1">Code</button>
                                <button type="button" class="trix-button trix-button--icon trix-button--icon-bullet-list" data-trix-attribute="bullet" title="Aufzählung" tabindex="-1">Aufzählung</button>
                                <button type="button" class="trix-button trix-button--icon trix-button--icon-number-list" data-trix-attribute="number" title="Nummerierung" tabindex="-1">Nummerierung</button>
                                <button type="button" class="trix-button trix-button--icon trix-button--icon-decrease-nesting-level" data-trix-action="decreaseNestingLevel" title="Einzug verringern" tabindex="-1">Einzug verringern</button>
                                <button type="button" class="trix-button trix-button--icon trix-button--icon-increase-nesting-level" data-trix-action="increaseNestingLevel" title="Einzug erhöhen" tabindex="-1">Einzug erhöhen</button>
                            </span>

                            <span class="trix-button-group trix-button-group--file-tools" data-trix-button-group="file-tools">
                                <button type="button" class="trix-button trix-button--icon trix-button--icon-attach" data-trix-action="attachFiles" title="Bild einfügen" tabindex="-1">Bild einfügen</button>
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

                    <input id="page-content-input-{{ $formInstance }}" type="hidden" value="{{ $form->content }}">
                    <trix-editor
                        toolbar="page-content-toolbar-{{ $formInstance }}"
                        input="page-content-input-{{ $formInstance }}"
                        class="trix-content block w-full rounded-lg border border-zinc-300 dark:border-zinc-700"
                        x-on:trix-attachment-add="window.uploadPageAttachment($event, @js($editingPageId), (newId) => $wire.attachExistingPage(newId))"
                    ></trix-editor>
                </div>
                <flux:error name="form.content" />
            </flux:field>

            <flux:field>
                <flux:label>Meta-Beschreibung</flux:label>
                <flux:textarea wire:model="form.meta_description" rows="2" />
                <flux:description>Wird für Suchmaschinen im HTML-Head ausgegeben.</flux:description>
                <flux:error name="form.meta_description" />
            </flux:field>

            <flux:field>
                <flux:label>Status</flux:label>
                <flux:select wire:model="form.status">
                    <flux:select.option value="draft">Entwurf</flux:select.option>
                    <flux:select.option value="published">Veröffentlicht</flux:select.option>
                </flux:select>
                <flux:error name="form.status" />
            </flux:field>

            <flux:field variant="inline">
                <flux:checkbox wire:model="form.show_in_navigation" />
                <flux:label>In der Navigation anzeigen</flux:label>
                <flux:error name="form.show_in_navigation" />
            </flux:field>

            <flux:field>
                <flux:label>Sortierung</flux:label>
                <flux:input type="number" wire:model="form.sort_order" />
                <flux:error name="form.sort_order" />
            </flux:field>

            <flux:field>
                <flux:label>Anhänge</flux:label>
                <flux:description>PDF, ZIP, Word oder Excel, bis 10 MB. Werden auf der Seite als Download-Liste angezeigt.</flux:description>

                @if ($this->downloads->isNotEmpty())
                    <ul class="space-y-2">
                        @foreach ($this->downloads as $attachment)
                            <li wire:key="attachment-{{ $attachment->id }}" class="flex items-center justify-between rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700">
                                <span class="text-sm">
                                    {{ $attachment->original_filename }}
                                    <span class="text-zinc-500">({{ $attachment->formattedSize() }})</span>
                                </span>
                                <flux:button
                                    size="sm"
                                    icon="trash"
                                    variant="danger"
                                    wire:click="deleteAttachment({{ $attachment->id }})"
                                    wire:confirm="Anhang wirklich löschen?"
                                />
                            </li>
                        @endforeach
                    </ul>
                @endif

                <input type="file" wire:model="newDownloads" multiple class="text-sm">
                <flux:error name="newDownloads.*" />
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
            <flux:heading size="lg">Seite löschen?</flux:heading>
            <flux:text>
                Die Seite wird unwiderruflich gelöscht.
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
