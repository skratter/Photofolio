<div
    x-data="photoUploader()"
    x-on:livewire-upload-start="isUploading = true"
    x-on:livewire-upload-finish="isUploading = false; progress = 0"
    x-on:livewire-upload-error="isUploading = false"
    x-on:livewire-upload-progress="progress = $event.detail.progress"
    x-on:batch-uploaded.window="handleBatchUploaded($event.detail.results)"
>
    <label
        x-on:dragover.prevent="isDraggingOver = true"
        x-on:dragleave.prevent="isDraggingOver = false"
        x-on:drop.prevent="isDraggingOver = false; handleFiles($event.dataTransfer.files)"
        x-bind:class="isDraggingOver ? 'border-zinc-400 dark:border-zinc-600' : 'border-zinc-300 dark:border-zinc-700'"
        class="flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed p-8 text-center hover:border-zinc-400 dark:hover:border-zinc-600"
    >
        <input type="file" x-on:change="handleFiles($event.target.files); $event.target.value = null"
            multiple accept="image/*" class="hidden">
        <flux:icon name="arrow-up-tray" class="size-8 text-zinc-400" />
        <flux:text class="mt-2">Fotos hierher ziehen oder klicken zum Hochladen</flux:text>
    </label>

    {{-- Never touched by the user directly - only this component's own JS
         assigns files to it, one small batch at a time, to stay under PHP's
         per-request max_file_uploads limit. --}}
    <input type="file" x-ref="uploader" wire:model="photos" multiple class="hidden" tabindex="-1" aria-hidden="true">

    <div x-show="queue.length > 0" x-cloak class="mt-4">
        <div class="mb-2 flex items-center justify-between text-sm text-zinc-500">
            <span x-text="`${finished} von ${total} hochgeladen`"></span>
        </div>

        <div x-show="isUploading" x-cloak class="mb-3 h-2 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
            <div class="h-full bg-blue-500 transition-all" x-bind:style="`width: ${progress}%`"></div>
        </div>

        <ul class="max-h-64 space-y-1 overflow-y-auto text-sm">
            <template x-for="file in queue" :key="file.name">
                <li class="flex items-center justify-between gap-2">
                    <span x-text="file.name" class="truncate"></span>
                    <span
                        x-text="({ wartend: 'Wartet', laedt: 'Lädt hoch…', fertig: 'Fertig', fehler: 'Fehler' })[file.status]"
                        x-bind:class="{
                            'text-zinc-400': file.status === 'wartend',
                            'text-blue-500': file.status === 'laedt',
                            'text-green-600 dark:text-green-400': file.status === 'fertig',
                            'text-red-500': file.status === 'fehler',
                        }"
                        class="shrink-0"
                    ></span>
                </li>
            </template>
        </ul>
    </div>
</div>
