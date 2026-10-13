<?php

namespace App\Support;

/**
 * Hilfen zum korrekten Notieren von Tönen: Welcher Buchstabe mit welchem
 * Vorzeichen zu einem Halbton gehört, hängt vom musikalischen Zusammenhang ab
 * (Terz von D-Dur ist Fis, nicht Ges).
 */
class NoteSpelling
{
    public const LETTERS = ['C', 'D', 'E', 'F', 'G', 'A', 'B'];

    private const LETTER_SEMITONES = [0, 2, 4, 5, 7, 9, 11];

    /**
     * Halbton (0–11) eines Tons in ASCII-Schreibweise, z. B. "F#" oder "Bb".
     */
    public static function pitchClass(string $note): int
    {
        $semitone = self::LETTER_SEMITONES[self::letterIndex($note)];

        return match ($note[1] ?? '') {
            '#' => ($semitone + 1) % 12,
            'b' => ($semitone + 11) % 12,
            default => $semitone,
        };
    }

    public static function letterIndex(string $note): int
    {
        return (int) array_search($note[0], self::LETTERS, true);
    }

    /**
     * Schreibt einen Halbton mit dem vorgegebenen Buchstaben, z. B. Buchstabe
     * E und Halbton 3 ergibt "Eb".
     */
    public static function spell(int $letterIndex, int $pitchClass): string
    {
        $offset = (($pitchClass - self::LETTER_SEMITONES[$letterIndex] + 18) % 12) - 6;
        $accidental = $offset >= 0 ? str_repeat('#', $offset) : str_repeat('b', -$offset);

        return self::LETTERS[$letterIndex].$accidental;
    }

    /**
     * Tauscht die ASCII-Vorzeichen gegen echte Notationszeichen (♯ ♭).
     */
    public static function display(string $note): string
    {
        return str_replace(['##', '#', 'bb', 'b'], ['𝄪', '♯', '𝄫', '♭'], $note);
    }
}
