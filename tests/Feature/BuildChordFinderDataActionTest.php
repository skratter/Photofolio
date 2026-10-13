<?php

use App\Actions\BuildChordFinderDataAction;

function chordFinderKey(string $id): array
{
    return collect((new BuildChordFinderDataAction)->execute())->firstWhere('id', $id);
}

test('it builds all twelve major keys', function () {
    $keys = (new BuildChordFinderDataAction)->execute();

    expect($keys)->toHaveCount(12)
        ->and(collect($keys)->every(fn (array $key) => count($key['scale']) === 7 && count($key['chords']) === 7))->toBeTrue();
});

test('it spells the scale with the correct accidentals', function (string $id, string $expectedScale) {
    $scale = collect(chordFinderKey($id)['scale'])->pluck('note')->implode(' ');

    expect($scale)->toBe($expectedScale);
})->with([
    'C' => ['C', 'C D E F G A B'],
    'Eb' => ['Eb', 'E♭ F G A♭ B♭ C D'],
    'F#' => ['F#', 'F♯ G♯ A♯ B C♯ D♯ E♯'],
    'Db' => ['Db', 'D♭ E♭ F G♭ A♭ B♭ C'],
]);

test('it derives the diatonic chord qualities', function () {
    $chords = collect(chordFinderKey('C')['chords']);

    expect($chords->pluck('name')->all())->toBe(['C', 'Dm', 'Em', 'F', 'G', 'Am', 'B°'])
        ->and($chords->pluck('number')->all())->toBe(['1', '2m', '3m', '4', '5', '6m', '7°'])
        ->and($chords->pluck('quality')->all())->toBe(['major', 'minor', 'minor', 'major', 'major', 'minor', 'diminished']);
});

test('it places chord notes on the scale keys of the keyboard', function () {
    $cm = chordFinderKey('Eb')['chords'][5];

    expect($cm['name'])->toBe('Cm')
        ->and(collect($cm['notes'])->pluck('note')->all())->toBe(['C', 'E♭', 'G'])
        ->and(collect($cm['notes'])->pluck('degree')->all())->toBe([6, 1, 3])
        ->and(collect($cm['notes'])->pluck('semitone')->all())->toBe([12, 15, 19]);
});

test('it keeps every chord within the two keyboard octaves', function () {
    $semitones = collect((new BuildChordFinderDataAction)->execute())
        ->flatMap(fn (array $key) => $key['chords'])
        ->flatMap(fn (array $chord) => collect($chord['notes'])->pluck('semitone'));

    expect($semitones->min())->toBeGreaterThanOrEqual(0)
        ->and($semitones->max())->toBeLessThanOrEqual(23);
});
