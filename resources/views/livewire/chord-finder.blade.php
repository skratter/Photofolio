<x-slot:head>
    <x-open-graph title="Akkordfinder"
        description="Tonart wählen, Akkord oder Stufe anklicken: Der Akkordfinder zeigt Tonleiter und Dreiklänge auf der Klaviatur, bestimmt Akkorde aus angeklickten Tasten und spielt Akkordfolgen ab." />
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

    $tabs = [
        'explore' => 'Finder',
        'identify' => 'Akkord bestimmen',
        'progression' => 'Akkordfolgen',
        'quiz' => 'Hörtraining',
    ];
@endphp

<div x-data="chordFinder(@js(['keys' => $keys, 'progressions' => $progressions]))" x-on:keydown.window="handleKey($event)"
    class="mx-auto max-w-5xl px-6 pt-8 pb-16">
    <flux:heading size="xl">Akkordfinder</flux:heading>
    <flux:text class="mt-2 max-w-2xl text-zinc-500">
        Jede Tonart hat sieben Stufenakkorde. Finde sie auf der Klaviatur, lass dir einen Akkord aus angeklickten Tasten
        bestimmen, spiel gängige Akkordfolgen ab oder trainiere dein Gehör.
    </flux:text>

    <div role="tablist" aria-label="Bereich"
        class="mt-6 mb-6 flex w-fit max-w-full flex-wrap gap-1 rounded-lg bg-zinc-100 p-1 dark:bg-zinc-900">
        @foreach ($tabs as $tabId => $tabLabel)
            <button type="button" role="tab" x-on:click="mode = '{{ $tabId }}'" x-bind:aria-selected="mode === '{{ $tabId }}'"
                x-bind:class="mode === '{{ $tabId }}'
                    ? 'bg-white text-zinc-900 shadow-sm dark:bg-zinc-700 dark:text-white'
                    : 'text-zinc-500 hover:text-zinc-900 dark:hover:text-white'"
                class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors">{{ $tabLabel }}</button>
        @endforeach
    </div>

    {{-- Auswahl (Finder, Akkordfolgen, Hörtraining) --}}
    <div x-show="mode !== 'identify'" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <flux:field>
            <flux:label>Tonart</flux:label>
            <flux:select x-model="keyId">
                <optgroup label="Dur">
                    <template x-for="option in majorKeys" :key="option.id">
                        <option :value="option.id" x-text="display(option.label)"></option>
                    </template>
                </optgroup>
                <optgroup label="Moll">
                    <template x-for="option in minorKeys" :key="option.id">
                        <option :value="option.id" x-text="display(option.label)"></option>
                    </template>
                </optgroup>
            </flux:select>
        </flux:field>

        <div x-show="mode === 'explore'">
            <flux:field>
                <flux:label class="flex items-center gap-2"><span class="size-2 rounded-full bg-red-500"></span> Akkord</flux:label>
                <flux:select x-model.number="degree">
                    <template x-for="option in chordList" :key="option.degree">
                        <option :value="option.degree" x-text="display(option.name)"></option>
                    </template>
                </flux:select>
            </flux:field>
        </div>

        <div x-show="mode === 'explore'">
            <flux:field>
                <flux:label class="flex items-center gap-2"><span class="size-2 rounded-full bg-teal-400"></span> Stufe</flux:label>
                <flux:select x-model.number="degree">
                    <template x-for="option in chordList" :key="option.degree">
                        <option :value="option.degree" x-text="option.number"></option>
                    </template>
                </flux:select>
            </flux:field>
        </div>

        <div x-show="mode === 'explore'">
            <flux:field>
                <flux:label>Lage</flux:label>
                <flux:select x-model.number="inversion">
                    <template x-for="(label, index) in inversionLabels" :key="index">
                        <option :value="index" x-text="label"></option>
                    </template>
                </flux:select>
            </flux:field>
        </div>
    </div>

    {{-- Finder: Zusammenfassung --}}
    <div x-show="mode === 'explore'"
        class="mt-6 flex flex-wrap items-center gap-x-6 gap-y-3 rounded-xl border border-zinc-200 bg-zinc-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-baseline gap-3 text-3xl font-bold italic">
            <span class="text-red-500" x-text="currentChord.number"></span>
            <span class="text-zinc-400">=</span>
            <span x-text="chordTitle"></span>
        </div>

        <div class="text-sm text-zinc-600 dark:text-zinc-300">
            <span x-text="inversionLabels[inversion]"></span>:
            <span class="font-semibold text-red-500" x-text="chordNotesText"></span>
        </div>

        <div class="flex flex-wrap gap-2">
            <flux:button size="sm" icon="play" x-on:click="playChord()">Akkord hören</flux:button>
            <flux:button size="sm" variant="ghost" icon="play" x-on:click="playScale()">Tonleiter hören</flux:button>
            <flux:button size="sm" variant="ghost" icon="link" x-on:click="copyLink()">
                <span x-text="copied ? 'Link kopiert' : 'Link kopieren'"></span>
            </flux:button>
        </div>

        <div class="text-sm text-zinc-500 sm:ml-auto">
            Tonleiter <span class="font-semibold text-teal-500" x-text="display(currentKey.label)"></span>:
            <span x-text="scaleNotesText"></span>
        </div>
    </div>

    {{-- Akkord bestimmen --}}
    <div x-show="mode === 'identify'" x-cloak aria-live="polite"
        class="rounded-xl border border-zinc-200 bg-zinc-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-900">
        <p x-show="picked.length === 0" class="text-sm text-zinc-500">
            Tippe auf der Klaviatur die Tasten an, die zusammen erklingen sollen. Der Akkordfinder sagt dir, welcher Akkord
            das ist, auch bei Umkehrungen und Septakkorden. Mit einem MIDI-Keyboard geht es noch direkter.
        </p>

        <div x-show="picked.length > 0">
            <div x-show="identified && identified.name" class="flex flex-wrap items-baseline gap-x-6 gap-y-2">
                <span class="text-3xl font-bold italic" x-text="display(identified?.name)"></span>
                <span class="text-sm text-zinc-600 dark:text-zinc-300">
                    <span x-text="identified?.quality"></span> ·
                    <span x-text="identified?.inversion"></span>
                </span>
            </div>

            <p x-show="identified && !identified.name" class="text-sm text-zinc-600 dark:text-zinc-300">
                Diese Töne ergeben keinen gängigen Akkord. Probier eine Taste mehr oder weniger.
            </p>

            <div class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-sm text-zinc-600 dark:text-zinc-300" x-show="identified">
                <span>Töne:
                    <template x-for="tone in identified?.tones ?? []" :key="tone.semitone">
                        <span class="mr-1 font-semibold text-red-500"
                            x-text="display(tone.note) + (tone.role ? ' (' + tone.role + ')' : '')"></span>
                    </template>
                </span>
                <span x-show="identified?.alternatives?.length > 0">Auch lesbar als:
                    <span class="font-semibold" x-text="(identified?.alternatives ?? []).map((name) => display(name)).join(', ')"></span>
                </span>
            </div>

            <div x-show="identified?.keys?.length > 0" class="mt-3">
                <p class="mb-1 text-xs tracking-wide text-zinc-500 uppercase">Kommt als Stufenakkord vor in</p>
                <div class="flex flex-wrap gap-1.5">
                    <template x-for="entry in identified?.keys ?? []" :key="entry.label + entry.number">
                        <span class="rounded-full border border-zinc-300 px-2.5 py-0.5 text-xs dark:border-zinc-600">
                            <span x-text="display(entry.label)"></span>:
                            <span class="font-semibold" x-text="entry.number"></span>
                        </span>
                    </template>
                </div>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
            <flux:button size="sm" icon="play" x-on:click="playPicked()" x-bind:disabled="picked.length === 0">Anhören</flux:button>
            <flux:button size="sm" variant="ghost" icon="trash" x-on:click="resetPicked()" x-bind:disabled="picked.length === 0">Zurücksetzen</flux:button>
        </div>
    </div>

    {{-- Akkordfolgen --}}
    <div x-show="mode === 'progression'" x-cloak
        class="mt-6 space-y-4 rounded-xl border border-zinc-200 bg-zinc-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-wrap items-end gap-4">
            <flux:field class="min-w-52">
                <flux:label>Vorlage</flux:label>
                <flux:select x-model="presetIndex" x-on:change="applyPreset()">
                    <option value="">Eigene Folge</option>
                    <template x-for="(preset, index) in presets" :key="index">
                        <option :value="index" x-text="preset.label"></option>
                    </template>
                </flux:select>
            </flux:field>

            <label class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                Tempo
                <input type="range" min="50" max="160" step="5" x-model.number="bpm" aria-label="Tempo"
                    class="w-28 accent-red-500">
                <span class="w-16 tabular-nums" x-text="bpm + ' BPM'"></span>
            </label>

            <flux:checkbox x-model="loop" label="Wiederholen" />
        </div>

        <div>
            <p class="mb-1 text-xs tracking-wide text-zinc-500 uppercase">Akkord hinzufügen</p>
            <div class="flex flex-wrap gap-1.5">
                <template x-for="chord in chordList" :key="chord.degree">
                    <flux:button size="sm" x-on:click="addToProgression(chord.degree)">
                        <span x-text="chord.number + ' ' + display(chord.name)"></span>
                    </flux:button>
                </template>
            </div>
        </div>

        <div>
            <p class="mb-1 text-xs tracking-wide text-zinc-500 uppercase">Folge</p>
            <p x-show="progression.length === 0" class="text-sm text-zinc-500">
                Noch leer. Wähle eine Vorlage oder füge Akkorde hinzu.
            </p>
            <div class="flex flex-wrap gap-1.5">
                <template x-for="(degree, index) in progression" :key="index">
                    <span x-bind:class="playingIndex === index ? 'border-red-500 bg-red-500 text-white' : 'border-zinc-300 dark:border-zinc-600'"
                        class="flex items-center gap-1 rounded-full border py-1 pr-1 pl-3 text-sm font-semibold transition-colors">
                        <span x-text="progressionLabel(degree)"></span>
                        <button type="button" x-on:click="removeFromProgression(index)" aria-label="Akkord entfernen"
                            class="flex size-5 items-center justify-center rounded-full opacity-60 hover:opacity-100">
                            <flux:icon name="x-mark" class="size-3.5" />
                        </button>
                    </span>
                </template>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            <flux:button size="sm" icon="play" x-show="!isPlayingProgression" x-on:click="playProgression()"
                x-bind:disabled="progression.length === 0">Abspielen</flux:button>
            <flux:button size="sm" icon="stop" x-show="isPlayingProgression" x-cloak x-on:click="stopProgression()">Stopp</flux:button>
            <flux:button size="sm" variant="ghost" icon="trash" x-on:click="clearProgression()"
                x-bind:disabled="progression.length === 0">Leeren</flux:button>
        </div>
    </div>

    {{-- Hörtraining --}}
    <div x-show="mode === 'quiz'" x-cloak
        class="mt-6 space-y-4 rounded-xl border border-zinc-200 bg-zinc-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-900">
        <p class="text-sm text-zinc-600 dark:text-zinc-300">
            Du hörst zuerst die 1 (Tonika) als Orientierung, dann einen gesuchten Akkord der gewählten Tonart. Welche Stufe war es?
        </p>

        <div class="flex flex-wrap items-end gap-4">
            <flux:field class="min-w-52">
                <flux:label>Schwierigkeit</flux:label>
                <flux:select x-model="quiz.level">
                    <option value="easy">Leicht (1, 4, 5)</option>
                    <option value="medium">Mittel (1, 2, 4, 5, 6)</option>
                    <option value="hard">Schwer (alle Stufen)</option>
                </flux:select>
            </flux:field>

            <div class="flex gap-2">
                <flux:button size="sm" icon="play" x-on:click="startQuiz()">
                    <span x-text="quiz.target ? 'Neue Aufgabe' : 'Start'"></span>
                </flux:button>
                <flux:button size="sm" variant="ghost" icon="arrow-path" x-on:click="replayQuiz()" x-bind:disabled="!quiz.target">Nochmal hören</flux:button>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            <template x-for="degree in quizDegrees" :key="degree">
                <flux:button size="sm" x-on:click="answerQuiz(degree)" x-bind:disabled="!quiz.target || quiz.answered"
                    x-bind:class="quiz.answered && degree === quiz.target
                        ? 'border-teal-500! bg-teal-500! text-white!'
                        : (quiz.answered && degree === quiz.answer ? 'border-red-500! bg-red-500! text-white!' : '')">
                    <span x-text="chordList[degree - 1].number"></span>
                </flux:button>
            </template>
        </div>

        <p x-show="quiz.answered" class="text-sm font-semibold" x-text="quizFeedback" aria-live="polite"></p>

        <p class="flex flex-wrap items-center gap-x-4 text-sm text-zinc-500">
            <span>Richtig: <span class="font-semibold" x-text="quiz.score + ' von ' + quiz.total"></span></span>
            <span>Serie: <span class="font-semibold" x-text="quiz.streak"></span></span>
            <button type="button" class="underline hover:no-underline" x-on:click="resetQuizScore()">Zurücksetzen</button>
        </p>
    </div>

    {{-- Anzeige und Klang --}}
    <div class="mt-4 flex flex-wrap items-center gap-2">
        {{-- Feste Breite, damit die Buttons beim Umschalten von "an" auf "aus"
             nicht springen; das Lämpchen leuchtet nur, wenn der Button an ist. --}}
        <flux:button size="sm" class="w-36 justify-center" x-show="mode !== 'identify'" x-on:click="showScale = ! showScale"
            x-bind:variant="showScale ? 'primary' : 'outline'">
            <span x-bind:class="showScale ? 'bg-teal-400 shadow-[0_0_8px_rgba(45,212,191,0.9)]' : 'bg-zinc-400/50'"
                class="mr-1.5 inline-block size-2 rounded-full transition-colors"></span>
            Tonleiter <span x-text="showScale ? 'an' : 'aus'" class="inline-block w-6 text-left"></span>
        </flux:button>
        <flux:button size="sm" class="w-36 justify-center" x-show="mode === 'explore' || mode === 'progression'" x-on:click="showChord = ! showChord"
            x-bind:variant="showChord ? 'primary' : 'outline'">
            <span x-bind:class="showChord ? 'bg-red-500 shadow-[0_0_8px_rgba(239,68,68,0.9)]' : 'bg-zinc-400/50'"
                class="mr-1.5 inline-block size-2 rounded-full transition-colors"></span>
            Akkord <span x-text="showChord ? 'an' : 'aus'" class="inline-block w-6 text-left"></span>
        </flux:button>

        <div x-show="mode !== 'identify'" class="flex">
            <flux:button size="sm" class="rounded-r-none" x-on:click="useSevenths = false"
                x-bind:variant="! useSevenths ? 'primary' : 'outline'">Dreiklänge</flux:button>
            <flux:button size="sm" class="-ml-px rounded-l-none" x-on:click="useSevenths = true"
                x-bind:variant="useSevenths ? 'primary' : 'outline'">Vierklänge</flux:button>
        </div>
    </div>

    <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-2">
        <flux:select size="sm" x-model="sound" aria-label="Klang" class="max-w-44">
            <option value="piano">Klang: Klavier</option>
            <option value="epiano">Klang: E-Piano</option>
            <option value="synth">Klang: Synthesizer</option>
            <option value="organ">Klang: Orgel</option>
            <option value="strings">Klang: Streicher</option>
        </flux:select>

        <label class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
            <flux:icon name="speaker-wave" class="size-4" />
            <span class="sr-only">Lautstärke</span>
            <input type="range" min="0" max="100" x-model.number="volume" aria-label="Lautstärke" class="w-28 accent-red-500">
        </label>

        <flux:select size="sm" x-model="notation" aria-label="Notation" class="max-w-52">
            <option value="english">Notation: C D E F G A B</option>
            <option value="german">Notation: C D E F G A H</option>
        </flux:select>

        <template x-if="midiSupported">
            <div class="flex items-center gap-2">
                <flux:button size="sm" variant="ghost" icon="musical-note" x-on:click="connectMidi()">MIDI-Keyboard verbinden</flux:button>
                <span class="text-xs text-zinc-500" x-text="midiStatus"></span>
            </div>
        </template>
    </div>

    <div class="mt-4 rounded-xl border border-zinc-200 bg-zinc-100 pb-5 dark:border-zinc-700 dark:bg-zinc-900">
        {{-- Auf schmalen Displays ist die Klaviatur breiter als der Bildschirm
             und wischbar, damit die Tasten groß genug zum Treffen bleiben. --}}
        <div x-ref="scroller" class="overflow-x-auto px-3 pt-28 pb-1 sm:px-5">
        <div x-ref="keys" x-on:pointerdown="glideStart($event)" x-on:pointermove="glideMove($event)"
            x-on:pointerup.window="glideEnd()" x-on:pointercancel.window="glideEnd()"
            class="relative min-w-[40rem] touch-pan-y sm:min-w-0">
            @foreach ($pianoKeys as $pianoKey)
                <div x-show="marker({{ $pianoKey['semitone'] }}) !== null" x-cloak
                    class="pointer-events-none absolute bottom-full flex -translate-x-1/2 flex-col items-center"
                    style="left: {{ $pianoKey['center'] }}%">
                    <span
                        x-bind:class="marker({{ $pianoKey['semitone'] }})?.type === 'chord'
                            ? 'border-red-500 bg-red-500 text-white shadow-[0_0_12px_rgba(239,68,68,0.8)]'
                            : 'border-teal-500 bg-transparent text-teal-600 dark:text-teal-400'"
                        class="flex h-6 min-w-6 items-center justify-center rounded-full border-2 px-1 text-[11px] font-bold sm:h-7 sm:min-w-7 sm:text-xs"
                        x-text="marker({{ $pianoKey['semitone'] }})?.degree"></span>
                    <span class="mt-0.5 text-[10px] font-semibold text-zinc-600 sm:text-xs dark:text-zinc-300"
                        x-text="marker({{ $pianoKey['semitone'] }})?.note"></span>
                    <span x-bind:class="isRaised({{ $pianoKey['semitone'] }}) ? 'h-11' : 'h-2'" class="w-px bg-zinc-400"></span>
                </div>
            @endforeach

            <div class="flex h-44 sm:h-64">
                @foreach ($pianoKeys->where('isBlack', false) as $pianoKey)
                    <button type="button" x-on:click="$event.detail === 0 && pressKey({{ $pianoKey['semitone'] }})"
                        data-semitone="{{ $pianoKey['semitone'] }}"
                        x-bind:aria-label="noteName({{ $pianoKey['semitone'] }})"
                        x-bind:aria-pressed="mode === 'identify' ? picked.includes({{ $pianoKey['semitone'] }}) : null"
                        x-bind:class="{
                            'bg-red-400 shadow-[0_10px_16px_-8px_rgba(239,68,68,0.9)]': state({{ $pianoKey['semitone'] }}) === 'chord',
                            'bg-teal-300': state({{ $pianoKey['semitone'] }}) === 'scale',
                            'bg-white hover:bg-zinc-100': state({{ $pianoKey['semitone'] }}) === null,
                            'brightness-90': glideSemitone === {{ $pianoKey['semitone'] }},
                        }"
                        class="flex-1 cursor-pointer touch-pan-y rounded-b-md border border-zinc-300 transition-colors select-none first:rounded-bl-lg last:rounded-br-lg focus-visible:z-20 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 active:brightness-90 dark:border-zinc-600"></button>
                @endforeach
            </div>

            @foreach ($pianoKeys->where('isBlack', true) as $pianoKey)
                <button type="button" x-on:click="$event.detail === 0 && pressKey({{ $pianoKey['semitone'] }})"
                    data-semitone="{{ $pianoKey['semitone'] }}"
                    x-bind:aria-label="noteName({{ $pianoKey['semitone'] }})"
                    x-bind:aria-pressed="mode === 'identify' ? picked.includes({{ $pianoKey['semitone'] }}) : null"
                    x-bind:class="{
                        'bg-red-500 shadow-[0_10px_16px_-8px_rgba(239,68,68,0.9)]': state({{ $pianoKey['semitone'] }}) === 'chord',
                        'bg-teal-500': state({{ $pianoKey['semitone'] }}) === 'scale',
                        'bg-zinc-900 hover:bg-zinc-700': state({{ $pianoKey['semitone'] }}) === null,
                        'brightness-150': glideSemitone === {{ $pianoKey['semitone'] }},
                    }"
                    class="absolute top-0 z-10 h-[60%] w-[4.5%] -translate-x-1/2 cursor-pointer touch-pan-y rounded-b-md border border-zinc-950 transition-colors select-none focus-visible:z-20 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 active:brightness-125"
                    style="left: {{ $pianoKey['center'] }}%"></button>
            @endforeach
        </div>
        </div>

        <div class="mt-5 flex items-center justify-center gap-2 px-3 text-center text-xs text-zinc-500">
            <flux:icon name="musical-note" class="size-4 shrink-0" />
            <span>
                <span x-show="mode !== 'identify'">Tasten anklicken oder antippen, über mehrere Tasten wischen zum Gleiten</span>
                <span x-show="mode === 'identify'" x-cloak>Tasten antippen, um sie zum Akkord hinzuzufügen oder zu entfernen</span>
                <span class="sm:hidden"> · Oberhalb der Tasten wischen verschiebt die Klaviatur</span>
            </span>
        </div>

        <p class="mt-1 hidden px-5 text-center text-xs text-zinc-400 sm:block">
            Computertastatur: A S D F G H J K L spielen die weißen, W E T Y U O P die schwarzen Tasten · 1–7 wählen die Stufe · Leertaste spielt den Akkord
        </p>

        <div class="mx-3 mt-4 flex flex-wrap items-center justify-center gap-x-8 gap-y-2 border-t border-zinc-200 pt-4 text-sm text-zinc-600 dark:border-zinc-700 dark:text-zinc-300">
            <span x-show="mode !== 'identify'" class="flex items-center gap-2"><span class="size-3 rounded-sm bg-teal-300"></span> Tonleiter (1–7)</span>
            <span class="flex items-center gap-2">
                <span class="size-3 rounded-sm bg-red-400"></span>
                <span x-text="mode === 'identify' ? 'Gewählte Töne (mit Funktion im Akkord)' : (mode === 'quiz' ? 'Gesuchter Akkord (nach der Antwort)' : 'Gewählter Akkord')"></span>
            </span>
        </div>
    </div>
</div>
