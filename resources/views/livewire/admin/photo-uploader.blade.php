<div
    x-data="{ isUploading: false, progress: 0 }"
    x-on:livewire-upload-start="isUploading = true"
    x-on:livewire-upload-finish="isUploading = false"
    x-on:livewire-upload-error="isUploading = false"
    x-on:livewire-upload-progress="progress = $event.detail.progress"
>
    <label class="flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-zinc-300 p-8 text-center hover:border-zinc-400 dark:border-zinc-700 dark:hover:border-zinc-600">
        <input type="file" wire:model="photos" multiple accept="image/*" class="hidden">
        <flux:icon name="arrow-up-tray" class="size-8 text-zinc-400" />
        <flux:text class="mt-2">Fotos hierher ziehen oder klicken zum Hochladen</flux:text>
    </label>

    <div x-show="isUploading" x-cloak class="mt-4 h-2 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
        <div class="h-full bg-blue-500 transition-all" x-bind:style="`width: ${progress}%`"></div>
    </div>

    @error('photos.*')
        <flux:text class="mt-2 text-red-500">{{ $message }}</flux:text>
    @enderror
</div>
