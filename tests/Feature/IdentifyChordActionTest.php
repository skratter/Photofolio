<?php

use App\Actions\IdentifyChordAction;

function identifyChord(array $semitones): array
{
    return (new IdentifyChordAction)->execute($semitones);
}

test('it names triads in root position', function (array $semitones, string $name, string $quality) {
    $result = identifyChord($semitones);

    expect($result['name'])->toBe($name)
        ->and($result['quality'])->toBe($quality)
        ->and($result['inversion'])->toBe('Grundstellung');
})->with([
    'C major' => [[0, 4, 7], 'C', 'Dur-Dreiklang'],
    'A minor' => [[9, 12, 16], 'Am', 'Moll-Dreiklang'],
    'B diminished' => [[11, 14, 17], 'B°', 'Verminderter Dreiklang'],
    'C augmented' => [[0, 4, 8], 'Caug', 'Übermäßiger Dreiklang'],
    'C sus4' => [[0, 5, 7], 'Csus4', 'Quartvorhalt'],
]);

test('it spells roots the way they are commonly written', function (array $semitones, string $name) {
    expect(identifyChord($semitones)['name'])->toBe($name);
})->with([
    'B-flat major' => [[10, 14, 17], 'B♭'],
    'E-flat major' => [[3, 7, 10], 'E♭'],
    'F-sharp major' => [[6, 10, 13], 'F♯'],
    'F-sharp minor' => [[6, 9, 13], 'F♯m'],
    'C-sharp minor' => [[1, 4, 8], 'C♯m'],
    'G-sharp minor' => [[8, 11, 15], 'G♯m'],
    'D-flat major' => [[1, 5, 8], 'D♭'],
]);

test('it spells the chord tones relative to the root', function () {
    $tones = collect(identifyChord([10, 14, 17])['tones']);

    expect($tones->pluck('note')->all())->toBe(['B♭', 'D', 'F'])
        ->and($tones->pluck('role')->all())->toBe(['R', '3', '5']);
});

test('it recognises inversions as slash chords', function () {
    $firstInversion = identifyChord([4, 7, 12]);
    $secondInversion = identifyChord([7, 12, 16]);

    expect($firstInversion['name'])->toBe('C/E')
        ->and($firstInversion['inversion'])->toBe('1. Umkehrung')
        ->and($secondInversion['name'])->toBe('C/G')
        ->and($secondInversion['inversion'])->toBe('2. Umkehrung');
});

test('it names seventh chords', function (array $semitones, string $name) {
    expect(identifyChord($semitones)['name'])->toBe($name);
})->with([
    'Cmaj7' => [[0, 4, 7, 11], 'Cmaj7'],
    'G7' => [[7, 11, 14, 17], 'G7'],
    'Dm7' => [[2, 5, 9, 12], 'Dm7'],
    'Bm7b5' => [[11, 14, 17, 21], 'Bm7♭5'],
]);

test('it prefers the chord whose root is the lowest note and lists the alternative reading', function () {
    $c6 = identifyChord([0, 4, 7, 9]);
    $am7 = identifyChord([9, 12, 16, 19]);

    expect($c6['name'])->toBe('C6')
        ->and($c6['alternatives'])->toContain('Am7/C')
        ->and($am7['name'])->toBe('Am7');
});

test('it ignores doubled notes in other octaves', function () {
    expect(identifyChord([0, 4, 7, 12, 16])['name'])->toBe('C');
});

test('it lists the keys in which the chord is a diatonic chord', function () {
    $keys = collect(identifyChord([0, 4, 7])['keys']);

    expect($keys->firstWhere('label', 'C-Dur')['number'])->toBe('1')
        ->and($keys->firstWhere('label', 'F-Dur')['number'])->toBe('5')
        ->and($keys->firstWhere('label', 'G-Dur')['number'])->toBe('4')
        ->and($keys->firstWhere('label', 'A-Moll')['number'])->toBe('3');
});

test('it returns no name for notes that form no known chord', function () {
    $result = identifyChord([0, 1]);

    expect($result['name'])->toBeNull()
        ->and($result['tones'])->toHaveCount(2)
        ->and($result['keys'])->toBeEmpty();
});

test('it copes with an empty selection', function () {
    expect(identifyChord([])['name'])->toBeNull();
});
