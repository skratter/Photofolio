<div class="mx-auto max-w-5xl px-6 pt-8 pb-16">
    <div class="mb-8">
        <flux:heading size="xl">{{ $album->title }}</flux:heading>
        @if ($album->description)
            <flux:text class="mt-2 text-zinc-500">{{ $album->description }}</flux:text>
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
        <x-photo-masonry :album="$album" :photos="$this->photos" />
    @endif
</div>
