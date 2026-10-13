<x-slot:head>
    <x-open-graph title="Akkordfinder"
        description="Tonart wählen, Akkord oder Stufe anklicken: Der Akkordfinder zeigt Tonleiter und Dreiklänge auf der Klaviatur und spielt sie ab." />
</x-slot:head>

@php
    $whiteSemitones = [0, 2, 4, 5, 7, 9, 11];
    $whiteKeyCount = 14;

    // Mitte jeder Taste in Prozent der Klaviatur-Breite: Weiße Tasten sind
    // gleich breit, schwarze sitzen auf der Grenze zur vorherigen weißen.
    $pianoKeys = collect(range(0, 23))->map(function (int $semitone) use ($whiteSemitones, $whiteKeyCount) {
        $pitchClass = $semitone % 12;
        $whiteIndex = intdiv($semitone, 12) * 7 + count(array_filter($whiteSemitones, fn ($white) => $white < $pitchClass));
        $isBlack = ! in_array($pitchClass, $whiteSemitones, true);

        return [
            'semitone' => $semitone,
            'isBlack' => $isBlack,
            'center' => $isBlack ? $whiteIndex / $whiteKeyCount * 100 : ($whiteIndex + 0.5) / $whiteKeyCount * 100,
        ];
    });
@endphp

<div x-data="chordFinder(@js($keys))" class="mx-auto max-w-5xl px-6 pt-8 pb-16">
    <flux:heading size="xl">Akkordfinder</flux:heading>
    <flux:text class="mt-2 mb-8 max-w-2xl text-zinc-500">
        Jede Tonart hat sieben Stufenakkorde. Wähle eine Tonart und einen Akkord (oder seine Stufe) und sieh, welche Tasten
        dazugehören. Ein Klick auf die Tasten spielt den Ton.
    </flux:text>

    <div class="grid gap-4 sm:grid-cols-3">
        <flux:field>
            <flux:label>Tonart</flux:label>
            <flux:select x-model="keyId">
                <template x-for="option in keys" :key="option.id">
                    <option :value="option.id" x-text="option.label"></option>
                </template>
            </flux:select>
        </flux:field>

        <flux:field>
            <flux:label class="flex items-center gap-2"><span class="size-2 rounded-full bg-sky-400"></span> Akkord</flux:label>
            <flux:select x-model.number="degree">
                <template x-for="option in currentKey.chords" :key="option.degree">
                    <option :value="option.degree" x-text="option.name"></option>
                </template>
            </flux:select>
        </flux:field>

        <flux:field>
            <flux:label class="flex items-center gap-2"><span class="size-2 rounded-full bg-amber-400"></span> Stufe</flux:label>
            <flux:select x-model.number="degree">
                <template x-for="option in currentKey.chords" :key="option.degree">
                    <option :value="option.degree" x-text="option.number"></option>
                </template>
            </flux:select>
        </flux:field>
    </div>

    <div
        class="mt-6 flex flex-wrap items-center gap-x-6 gap-y-3 rounded-xl border border-zinc-200 bg-zinc-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-baseline gap-3 text-3xl font-bold italic">
            <span class="text-sky-500" x-text="currentChord.number"></span>
            <span class="text-zinc-400">=</span>
            <span x-text="currentChord.name"></span>
        </div>

        <div class="text-sm text-zinc-600 dark:text-zinc-300">
            Grundstellung: <span class="font-semibold text-sky-500" x-text="chordNotesText"></span>
        </div>

        <div class="flex gap-2">
            <flux:button size="sm" icon="play" x-on:click="playChord()">Akkord hören</flux:button>
            <flux:button size="sm" variant="ghost" icon="play" x-on:click="playScale()">Tonleiter hören</flux:button>
        </div>

        <div class="text-sm text-zinc-500 sm:ml-auto">
            Tonleiter <span class="font-semibold text-amber-500" x-text="currentKey.scale[0].note"></span>:
            <span x-text="scaleNotesText"></span>
        </div>
    </div>

    <div class="mt-4 flex flex-wrap gap-2">
        <flux:button size="sm" x-on:click="showScale = ! showScale" x-bind:variant="showScale ? 'primary' : 'outline'">
            <span class="mr-1.5 inline-block size-2 rounded-full bg-amber-400"></span>
            Tonleiter <span x-text="showScale ? 'an' : 'aus'"></span>
        </flux:button>
        <flux:button size="sm" x-on:click="showChord = ! showChord" x-bind:variant="showChord ? 'primary' : 'outline'">
            <span class="mr-1.5 inline-block size-2 rounded-full bg-sky-400"></span>
            Akkord <span x-text="showChord ? 'an' : 'aus'"></span>
        </flux:button>
    </div>

    <div class="mt-4 rounded-xl border border-zinc-200 bg-zinc-100 px-3 pt-28 pb-5 sm:px-5 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="relative">
            @foreach ($pianoKeys as $pianoKey)
                <div x-show="marker({{ $pianoKey['semitone'] }}) !== null" x-cloak
                    class="pointer-events-none absolute bottom-full flex -translate-x-1/2 flex-col items-center"
                    style="left: {{ $pianoKey['center'] }}%">
                    <span
                        x-bind:class="marker({{ $pianoKey['semitone'] }})?.type === 'chord'
                            ? 'border-sky-400 bg-sky-400 text-white shadow-[0_0_12px_rgba(56,189,248,0.8)]'
                            : 'border-amber-400 bg-transparent text-amber-600 dark:text-amber-400'"
                        class="flex size-6 items-center justify-center rounded-full border-2 text-[11px] font-bold sm:size-7 sm:text-xs"
                        x-text="marker({{ $pianoKey['semitone'] }})?.degree"></span>
                    <span class="mt-0.5 text-[10px] font-semibold text-zinc-600 sm:text-xs dark:text-zinc-300"
                        x-text="marker({{ $pianoKey['semitone'] }})?.note"></span>
                    <span x-bind:class="isRaised({{ $pianoKey['semitone'] }}) ? 'h-11' : 'h-2'" class="w-px bg-zinc-400"></span>
                </div>
            @endforeach

            <div class="flex h-44 sm:h-64">
                @foreach ($pianoKeys->where('isBlack', false) as $pianoKey)
                    <button type="button" x-on:click="playKey({{ $pianoKey['semitone'] }})"
                        x-bind:aria-label="noteName({{ $pianoKey['semitone'] }})"
                        x-bind:class="{
                            'bg-sky-400 shadow-[0_0_16px_rgba(56,189,248,0.8)]': state({{ $pianoKey['semitone'] }}) === 'chord',
                            'bg-amber-300': state({{ $pianoKey['semitone'] }}) === 'scale',
                            'bg-white hover:bg-zinc-100': state({{ $pianoKey['semitone'] }}) === null,
                        }"
                        class="flex-1 cursor-pointer rounded-b-md border border-zinc-300 transition-colors first:rounded-bl-lg last:rounded-br-lg dark:border-zinc-600"></button>
                @endforeach
            </div>

            @foreach ($pianoKeys->where('isBlack', true) as $pianoKey)
                <button type="button" x-on:click="playKey({{ $pianoKey['semitone'] }})"
                    x-bind:aria-label="noteName({{ $pianoKey['semitone'] }})"
                    x-bind:class="{
                        'bg-sky-400 shadow-[0_0_16px_rgba(56,189,248,0.8)]': state({{ $pianoKey['semitone'] }}) === 'chord',
                        'bg-amber-400': state({{ $pianoKey['semitone'] }}) === 'scale',
                        'bg-zinc-900 hover:bg-zinc-700': state({{ $pianoKey['semitone'] }}) === null,
                    }"
                    class="absolute top-0 z-10 h-[60%] w-[4.5%] -translate-x-1/2 cursor-pointer rounded-b-md border border-zinc-950 transition-colors"
                    style="left: {{ $pianoKey['center'] }}%"></button>
            @endforeach
        </div>

        <div class="mt-5 flex items-center justify-center gap-2 text-xs text-zinc-500">
            <flux:icon name="musical-note" class="size-4" /> Tasten anklicken oder antippen zum Anhören
        </div>

        <div class="mt-4 flex flex-wrap items-center justify-center gap-x-8 gap-y-2 border-t border-zinc-200 pt-4 text-sm text-zinc-600 dark:border-zinc-700 dark:text-zinc-300">
            <span class="flex items-center gap-2"><span class="size-3 rounded-sm bg-amber-300"></span> Tonleiter (1–7)</span>
            <span class="flex items-center gap-2"><span class="size-3 rounded-sm bg-sky-400"></span> Gewählter Akkord</span>
        </div>
    </div>
</div>
