<?php

namespace App\Actions;

/**
 * Baut die Tonleitern und Stufenakkorde aller 12 Dur-Tonarten für den
 * Akkordfinder. Halbtöne zählen ab dem C der ersten Oktave (0 = C, 12 = C eine
 * Oktave höher), damit die Klaviatur sie ohne Umrechnung anzeigen kann.
 */
class BuildChordFinderDataAction
{
    private const LETTERS = ['C', 'D', 'E', 'F', 'G', 'A', 'B'];

    private const LETTER_SEMITONES = [0, 2, 4, 5, 7, 9, 11];

    private const MAJOR_SCALE_STEPS = [0, 2, 4, 5, 7, 9, 11];

    private const KEYBOARD_SEMITONES = 24;

    /**
     * Grundtöne so geschrieben, wie sie in der Praxis üblich sind (Des statt
     * Cis, aber Fis statt Ges).
     *
     * @var list<string>
     */
    private const KEY_ROOTS = ['C', 'Db', 'D', 'Eb', 'E', 'F', 'F#', 'G', 'Ab', 'A', 'Bb', 'B'];

    /**
     * @return list<array{
     *     id: string,
     *     label: string,
     *     scale: list<array{degree: int, note: string, semitone: int}>,
     *     chords: list<array{degree: int, number: string, name: string, quality: string, notes: list<array{degree: int, note: string, semitone: int}>}>
     * }>
     */
    public function execute(): array
    {
        return array_map($this->buildKey(...), self::KEY_ROOTS);
    }

    /**
     * @return array{
     *     id: string,
     *     label: string,
     *     scale: list<array{degree: int, note: string, semitone: int}>,
     *     chords: list<array{degree: int, number: string, name: string, quality: string, notes: list<array{degree: int, note: string, semitone: int}>}>
     * }
     */
    private function buildKey(string $root): array
    {
        $rootLetterIndex = array_search($root[0], self::LETTERS, true);
        $rootSemitone = $this->semitoneOf($root);

        $scale = [];

        foreach (self::MAJOR_SCALE_STEPS as $index => $step) {
            $letterIndex = ($rootLetterIndex + $index) % 7;

            $scale[] = [
                'degree' => $index + 1,
                'note' => $this->displayName($this->spell($letterIndex, ($rootSemitone + $step) % 12)),
                'semitone' => $rootSemitone + $step,
            ];
        }

        return [
            'id' => $root,
            'label' => $this->displayName($root).'-Dur',
            'scale' => $scale,
            'chords' => array_map(fn (int $index) => $this->buildChord($scale, $index), range(0, 6)),
        ];
    }

    /**
     * Die Akkordtöne liegen auf denselben Tasten wie die Tonleiter: Terz und
     * Quinte sind die übernächsten Tonleitertöne, ab der 8. Stufe eine Oktave
     * höher. Würde der Akkord über die zwei Oktaven der Klaviatur hinausragen
     * (z. B. Stufe 7 in H-Dur), rutscht er komplett eine Oktave nach unten.
     *
     * @param  list<array{degree: int, note: string, semitone: int}>  $scale
     * @return array{degree: int, number: string, name: string, quality: string, notes: list<array{degree: int, note: string, semitone: int}>}
     */
    private function buildChord(array $scale, int $index): array
    {
        $notes = array_map(function (int $scaleIndex) use ($scale) {
            $note = $scale[$scaleIndex % 7];

            return [
                'degree' => $note['degree'],
                'note' => $note['note'],
                'semitone' => $note['semitone'] + intdiv($scaleIndex, 7) * 12,
            ];
        }, [$index, $index + 2, $index + 4]);

        if ($notes[2]['semitone'] >= self::KEYBOARD_SEMITONES) {
            $notes = array_map(fn (array $note) => [...$note, 'semitone' => $note['semitone'] - 12], $notes);
        }

        $thirdOffset = $notes[1]['semitone'] - $notes[0]['semitone'];
        $fifthOffset = $notes[2]['semitone'] - $notes[0]['semitone'];
        $degree = $index + 1;

        [$quality, $suffix] = match (true) {
            $thirdOffset === 4 => ['major', ''],
            $fifthOffset === 7 => ['minor', 'm'],
            default => ['diminished', '°'],
        };

        return [
            'degree' => $degree,
            'number' => $degree.$suffix,
            'name' => $this->displayName($scale[$index]['note']).$suffix,
            'quality' => $quality,
            'notes' => $notes,
        ];
    }

    private function semitoneOf(string $note): int
    {
        $semitone = self::LETTER_SEMITONES[array_search($note[0], self::LETTERS, true)];

        return match ($note[1] ?? '') {
            '#' => ($semitone + 1) % 12,
            'b' => ($semitone + 11) % 12,
            default => $semitone,
        };
    }

    private function spell(int $letterIndex, int $semitone): string
    {
        $offset = (($semitone - self::LETTER_SEMITONES[$letterIndex] + 18) % 12) - 6;

        $accidental = $offset >= 0 ? str_repeat('#', $offset) : str_repeat('b', -$offset);

        return self::LETTERS[$letterIndex].$accidental;
    }

    private function displayName(string $note): string
    {
        return str_replace(['##', '#', 'bb', 'b'], ['𝄪', '♯', '𝄫', '♭'], $note);
    }
}
