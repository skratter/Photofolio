<?php

namespace App\Actions;

use App\Support\NoteSpelling;

/**
 * Bestimmt aus angeklickten Tasten den Akkordnamen. Halbtöne zählen wie beim
 * Akkordfinder ab dem C der ersten Oktave; der tiefste Ton ist der Basston.
 *
 * Mehrdeutigkeiten (C6 = Am7 mit anderem Basston) löst die Reihenfolge der
 * Vorlagen auf: Zuerst gewinnt der Akkord, dessen Grundton der Basston ist,
 * dann der gebräuchlichere. Die übrigen Lesarten stehen unter "alternatives".
 *
 * @phpstan-type Tone array{semitone: int, note: string, role: string}
 * @phpstan-type Result array{
 *     name: string|null,
 *     quality: string|null,
 *     inversion: string|null,
 *     tones: list<Tone>,
 *     alternatives: list<string>,
 *     keys: list<array{label: string, number: string}>
 * }
 */
class IdentifyChordAction
{
    /**
     * Akkordvorlagen, nach Gebräuchlichkeit sortiert. Jeder Ton: [Halbtöne über
     * dem Grundton, Buchstabenschritte über dem Grundton (für die richtige
     * Schreibweise), Funktion im Akkord].
     *
     * @var list<array{0: string, 1: string, 2: list<array{0: int, 1: int, 2: string}>}>
     */
    private const TEMPLATES = [
        ['', 'Dur-Dreiklang', [[0, 0, 'R'], [4, 2, '3'], [7, 4, '5']]],
        ['m', 'Moll-Dreiklang', [[0, 0, 'R'], [3, 2, '♭3'], [7, 4, '5']]],
        ['°', 'Verminderter Dreiklang', [[0, 0, 'R'], [3, 2, '♭3'], [6, 4, '♭5']]],
        ['aug', 'Übermäßiger Dreiklang', [[0, 0, 'R'], [4, 2, '3'], [8, 4, '♯5']]],
        ['sus4', 'Quartvorhalt', [[0, 0, 'R'], [5, 3, '4'], [7, 4, '5']]],
        ['sus2', 'Sekundvorhalt', [[0, 0, 'R'], [2, 1, '2'], [7, 4, '5']]],
        ['5', 'Quinte (Powerchord)', [[0, 0, 'R'], [7, 4, '5']]],
        ['maj7', 'Großer Septakkord', [[0, 0, 'R'], [4, 2, '3'], [7, 4, '5'], [11, 6, '7']]],
        ['7', 'Dominantseptakkord', [[0, 0, 'R'], [4, 2, '3'], [7, 4, '5'], [10, 6, '♭7']]],
        ['m7', 'Moll-Septakkord', [[0, 0, 'R'], [3, 2, '♭3'], [7, 4, '5'], [10, 6, '♭7']]],
        ['m7♭5', 'Halbverminderter Septakkord', [[0, 0, 'R'], [3, 2, '♭3'], [6, 4, '♭5'], [10, 6, '♭7']]],
        ['°7', 'Verminderter Septakkord', [[0, 0, 'R'], [3, 2, '♭3'], [6, 4, '♭5'], [9, 6, '♭♭7']]],
        ['m(maj7)', 'Moll-Dur-Septakkord', [[0, 0, 'R'], [3, 2, '♭3'], [7, 4, '5'], [11, 6, '7']]],
        ['6', 'Sextakkord (Dur)', [[0, 0, 'R'], [4, 2, '3'], [7, 4, '5'], [9, 5, '6']]],
        ['m6', 'Sextakkord (Moll)', [[0, 0, 'R'], [3, 2, '♭3'], [7, 4, '5'], [9, 5, '6']]],
        ['add9', 'Dur mit hinzugefügter None', [[0, 0, 'R'], [4, 2, '3'], [7, 4, '5'], [14, 1, '9']]],
        ['m(add9)', 'Moll mit hinzugefügter None', [[0, 0, 'R'], [3, 2, '♭3'], [7, 4, '5'], [14, 1, '9']]],
        ['9', 'Dominantnonakkord', [[0, 0, 'R'], [4, 2, '3'], [7, 4, '5'], [10, 6, '♭7'], [14, 1, '9']]],
        ['maj9', 'Große None', [[0, 0, 'R'], [4, 2, '3'], [7, 4, '5'], [11, 6, '7'], [14, 1, '9']]],
        ['m9', 'Moll-Nonakkord', [[0, 0, 'R'], [3, 2, '♭3'], [7, 4, '5'], [10, 6, '♭7'], [14, 1, '9']]],
    ];

    /**
     * Grundtonschreibweise je Halbton: bei Dur-artigen Akkorden wie in den
     * gängigen Dur-Tonarten (Des, Es, As, B, aber Fis), bei Moll-artigen wie
     * in den gängigen Moll-Tonarten (cis, es, gis, b, fis).
     *
     * @var array<string, list<string>>
     */
    private const ROOT_NAMES = [
        'major' => ['C', 'Db', 'D', 'Eb', 'E', 'F', 'F#', 'G', 'Ab', 'A', 'Bb', 'B'],
        'minor' => ['C', 'C#', 'D', 'Eb', 'E', 'F', 'F#', 'G', 'G#', 'A', 'Bb', 'B'],
    ];

    /**
     * @param  list<int>  $semitones
     * @return Result
     */
    public function execute(array $semitones): array
    {
        $semitones = array_values(array_unique($semitones));
        sort($semitones);

        $lowestByPitchClass = [];

        foreach ($semitones as $semitone) {
            $lowestByPitchClass[$semitone % 12] ??= $semitone;
        }

        $pitchClasses = array_keys($lowestByPitchClass);
        sort($pitchClasses);

        $bass = $semitones === [] ? 0 : $semitones[0] % 12;
        $candidates = $this->candidates($pitchClasses);

        usort($candidates, fn (array $a, array $b) => [$a['root'] !== $bass, $a['template']] <=> [$b['root'] !== $bass, $b['template']]);

        if ($candidates === []) {
            return [
                'name' => null,
                'quality' => null,
                'inversion' => null,
                'tones' => array_map(fn (int $semitone) => [
                    'semitone' => $semitone,
                    'note' => NoteSpelling::display(self::ROOT_NAMES['major'][$semitone % 12]),
                    'role' => '',
                ], array_values($lowestByPitchClass)),
                'alternatives' => [],
                'keys' => [],
            ];
        }

        $primary = $this->describe($candidates[0]['root'], $candidates[0]['template'], $lowestByPitchClass, $bass);

        $alternatives = [];

        foreach (array_slice($candidates, 1) as $candidate) {
            $name = $this->describe($candidate['root'], $candidate['template'], $lowestByPitchClass, $bass)['name'];

            if ($name !== $primary['name'] && ! in_array($name, $alternatives, true)) {
                $alternatives[] = $name;
            }
        }

        return [
            'name' => $primary['name'],
            'quality' => $primary['quality'],
            'inversion' => $primary['inversion'],
            'tones' => $primary['tones'],
            'alternatives' => array_slice($alternatives, 0, 4),
            'keys' => $this->keysContaining($pitchClasses),
        ];
    }

    /**
     * @param  list<int>  $pitchClasses
     * @return list<array{root: int, template: int}>
     */
    private function candidates(array $pitchClasses): array
    {
        $candidates = [];

        foreach ($pitchClasses as $root) {
            $intervals = array_map(fn (int $pitchClass) => ($pitchClass - $root + 12) % 12, $pitchClasses);
            sort($intervals);

            foreach (self::TEMPLATES as $index => [, , $tones]) {
                $templateIntervals = array_map(fn (array $tone) => $tone[0] % 12, $tones);
                sort($templateIntervals);

                if ($templateIntervals === $intervals) {
                    $candidates[] = ['root' => $root, 'template' => $index];
                }
            }
        }

        return $candidates;
    }

    /**
     * @param  array<int, int>  $lowestByPitchClass
     * @return array{name: string, quality: string, inversion: string, tones: list<Tone>}
     */
    private function describe(int $root, int $templateIndex, array $lowestByPitchClass, int $bass): array
    {
        [$suffix, $quality, $toneDefinitions] = self::TEMPLATES[$templateIndex];

        $isMinorish = in_array(3, array_column($toneDefinitions, 0), true);
        $rootName = self::ROOT_NAMES[$isMinorish ? 'minor' : 'major'][$root];
        $rootLetterIndex = NoteSpelling::letterIndex($rootName);

        $byPitchClass = [];

        foreach ($toneDefinitions as $position => [$interval, $letterSteps, $role]) {
            $pitchClass = ($root + $interval) % 12;

            $byPitchClass[$pitchClass] = [
                'note' => NoteSpelling::display(NoteSpelling::spell(($rootLetterIndex + $letterSteps) % 7, $pitchClass)),
                'role' => $role,
                'position' => $position,
            ];
        }

        $tones = [];

        foreach ($lowestByPitchClass as $pitchClass => $semitone) {
            $tones[] = [
                'semitone' => $semitone,
                'note' => $byPitchClass[$pitchClass]['note'],
                'role' => $byPitchClass[$pitchClass]['role'],
            ];
        }

        usort($tones, fn (array $a, array $b) => $a['semitone'] <=> $b['semitone']);

        $bassTone = $byPitchClass[$bass];

        return [
            'name' => NoteSpelling::display($rootName).$suffix.($bass !== $root ? '/'.$bassTone['note'] : ''),
            'quality' => $quality,
            'inversion' => $bassTone['position'] === 0 ? 'Grundstellung' : $bassTone['position'].'. Umkehrung',
            'tones' => $tones,
        ];
    }

    /**
     * Alle Tonarten, in denen die Töne als Stufenakkord (Dreiklang oder
     * Septakkord) vorkommen.
     *
     * @param  list<int>  $pitchClasses
     * @return list<array{label: string, number: string}>
     */
    private function keysContaining(array $pitchClasses): array
    {
        $matches = [];

        foreach ((new BuildChordFinderDataAction)->execute() as $key) {
            foreach ([...$key['chords'], ...$key['sevenths']] as $chord) {
                $chordPitchClasses = array_values(array_unique(array_map(fn (array $note) => $note['semitone'] % 12, $chord['notes'])));
                sort($chordPitchClasses);

                if ($chordPitchClasses === $pitchClasses) {
                    $matches[] = ['label' => $key['label'], 'number' => $chord['number']];
                }
            }
        }

        return $matches;
    }
}
