<?php

namespace App\Actions;

use App\Support\NoteSpelling;

/**
 * Baut die Tonleitern und Stufenakkorde (Dreiklänge und Septakkorde) aller
 * 12 Dur- und 12 Moll-Tonarten für den Akkordfinder. Halbtöne zählen ab dem C
 * der ersten Oktave (0 = C, 12 = C eine Oktave höher), damit die Klaviatur sie
 * ohne Umrechnung anzeigen kann.
 *
 * @phpstan-type Note array{degree: int, note: string, semitone: int}
 * @phpstan-type Chord array{degree: int, number: string, name: string, quality: string, notes: list<Note>}
 * @phpstan-type Key array{id: string, label: string, mode: string, scale: list<Note>, chords: list<Chord>, sevenths: list<Chord>}
 */
class BuildChordFinderDataAction
{
    private const MAJOR_SCALE_STEPS = [0, 2, 4, 5, 7, 9, 11];

    private const MINOR_SCALE_STEPS = [0, 2, 3, 5, 7, 8, 10];

    private const KEYBOARD_SEMITONES = 24;

    /**
     * Grundtöne so geschrieben, wie die Tonarten in der Praxis üblich sind
     * (Des-Dur statt Cis-Dur, aber Fis-Dur statt Ges-Dur; bei Moll umgekehrt
     * cis-Moll statt des-Moll).
     *
     * @var array<string, list<string>>
     */
    private const KEY_ROOTS = [
        'major' => ['C', 'Db', 'D', 'Eb', 'E', 'F', 'F#', 'G', 'Ab', 'A', 'Bb', 'B'],
        'minor' => ['C', 'C#', 'D', 'Eb', 'E', 'F', 'F#', 'G', 'G#', 'A', 'Bb', 'B'],
    ];

    /**
     * @return list<Key>
     */
    public function execute(): array
    {
        $keys = [];

        foreach (self::KEY_ROOTS as $mode => $roots) {
            foreach ($roots as $root) {
                $keys[] = $this->buildKey($root, $mode);
            }
        }

        return $keys;
    }

    /**
     * Gängige Akkordfolgen als Stufenzahlen, getrennt nach Dur und Moll.
     *
     * @return array{major: list<array{label: string, degrees: list<int>}>, minor: list<array{label: string, degrees: list<int>}>}
     */
    public function progressions(): array
    {
        return [
            'major' => [
                ['label' => 'Pop-Klassiker', 'degrees' => [1, 5, 6, 4]],
                ['label' => '50er-Jahre-Folge', 'degrees' => [1, 6, 4, 5]],
                ['label' => 'Kadenz', 'degrees' => [1, 4, 5, 1]],
                ['label' => 'Jazz ii–V–I', 'degrees' => [2, 5, 1]],
                ['label' => 'Pachelbel-Kanon', 'degrees' => [1, 5, 6, 3, 4, 1, 4, 5]],
                ['label' => '12-Takt-Blues', 'degrees' => [1, 1, 1, 1, 4, 4, 1, 1, 5, 4, 1, 5]],
            ],
            'minor' => [
                ['label' => 'Episch', 'degrees' => [1, 6, 3, 7]],
                ['label' => 'Rock-Moll', 'degrees' => [1, 7, 6, 7]],
                ['label' => 'Kadenz', 'degrees' => [1, 4, 5, 1]],
                ['label' => 'Moll ii°–v–i', 'degrees' => [2, 5, 1]],
            ],
        ];
    }

    /**
     * @return Key
     */
    private function buildKey(string $root, string $mode): array
    {
        $steps = $mode === 'major' ? self::MAJOR_SCALE_STEPS : self::MINOR_SCALE_STEPS;
        $rootLetterIndex = NoteSpelling::letterIndex($root);
        $rootSemitone = NoteSpelling::pitchClass($root);

        $scale = [];

        foreach ($steps as $index => $step) {
            $letterIndex = ($rootLetterIndex + $index) % 7;

            $scale[] = [
                'degree' => $index + 1,
                'note' => NoteSpelling::display(NoteSpelling::spell($letterIndex, ($rootSemitone + $step) % 12)),
                'semitone' => $rootSemitone + $step,
            ];
        }

        return [
            'id' => $mode === 'major' ? $root : $root.'m',
            'label' => NoteSpelling::display($root).($mode === 'major' ? '-Dur' : '-Moll'),
            'mode' => $mode,
            'scale' => $scale,
            'chords' => array_map(fn (int $index) => $this->buildChord($scale, $index, 3), range(0, 6)),
            'sevenths' => array_map(fn (int $index) => $this->buildChord($scale, $index, 4), range(0, 6)),
        ];
    }

    /**
     * Die Akkordtöne liegen auf denselben Tasten wie die Tonleiter: Terz,
     * Quinte (und Septime) sind die jeweils übernächsten Tonleitertöne, ab der
     * 8. Stufe eine Oktave höher. Würde der Akkord über die zwei Oktaven der
     * Klaviatur hinausragen (z. B. Stufe 7 in H-Dur), rutscht er komplett eine
     * Oktave nach unten.
     *
     * @param  list<Note>  $scale
     * @param  int<3, 4>  $size  3 = Dreiklang, 4 = Septakkord
     * @return Chord
     */
    private function buildChord(array $scale, int $index, int $size): array
    {
        $notes = array_map(function (int $scaleIndex) use ($scale) {
            $note = $scale[$scaleIndex % 7];

            return [
                'degree' => $note['degree'],
                'note' => $note['note'],
                'semitone' => $note['semitone'] + intdiv($scaleIndex, 7) * 12,
            ];
        }, array_slice([$index, $index + 2, $index + 4, $index + 6], 0, $size));

        if (max(array_column($notes, 'semitone')) >= self::KEYBOARD_SEMITONES) {
            $notes = array_map(fn (array $note) => [...$note, 'semitone' => $note['semitone'] - 12], $notes);
        }

        $third = $notes[1]['semitone'] - $notes[0]['semitone'];
        $fifth = $notes[2]['semitone'] - $notes[0]['semitone'];
        $seventh = isset($notes[3]) ? $notes[3]['semitone'] - $notes[0]['semitone'] : null;
        $degree = $index + 1;

        [$quality, $nameSuffix, $numberSuffix] = match (true) {
            $seventh === null && $third === 4 => ['major', '', ''],
            $seventh === null && $fifth === 7 => ['minor', 'm', 'm'],
            $seventh === null => ['diminished', '°', '°'],
            $third === 4 && $seventh === 11 => ['major7', 'maj7', 'maj7'],
            $third === 4 => ['dominant7', '7', '⁷'],
            $fifth === 7 => ['minor7', 'm7', 'm7'],
            default => ['halfDiminished7', 'm7♭5', 'm7♭5'],
        };

        return [
            'degree' => $degree,
            'number' => $degree.$numberSuffix,
            'name' => $scale[$index]['note'].$nameSuffix,
            'quality' => $quality,
            'notes' => $notes,
        ];
    }
}
