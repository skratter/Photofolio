<?php

use App\Actions\BuildChordFinderDataAction;

function chordFinderKey(string $id): array
{
    return collect((new BuildChordFinderDataAction)->execute())->firstWhere('id', $id);
}

test('it builds twelve major and twelve minor keys', function () {
    $keys = collect((new BuildChordFinderDataAction)->execute());

    expect($keys)->toHaveCount(24)
        ->and($keys->where('mode', 'major'))->toHaveCount(12)
        ->and($keys->where('mode', 'minor'))->toHaveCount(12)
        ->and($keys->every(fn (array $key) => count($key['scale']) === 7 && count($key['chords']) === 7 && count($key['sevenths']) === 7))->toBeTrue();
});

test('it spells the scale with the correct accidentals', function (string $id, string $expectedScale) {
    $scale = collect(chordFinderKey($id)['scale'])->pluck('note')->implode(' ');

    expect($scale)->toBe($expectedScale);
})->with([
    'C' => ['C', 'C D E F G A B'],
    'Eb' => ['Eb', 'E♭ F G A♭ B♭ C D'],
    'F#' => ['F#', 'F♯ G♯ A♯ B C♯ D♯ E♯'],
    'Db' => ['Db', 'D♭ E♭ F G♭ A♭ B♭ C'],
    'Am' => ['Am', 'A B C D E F G'],
    'C#m' => ['C#m', 'C♯ D♯ E F♯ G♯ A B'],
    'Ebm' => ['Ebm', 'E♭ F G♭ A♭ B♭ C♭ D♭'],
]);

test('it labels the keys in German', function () {
    expect(chordFinderKey('Eb')['label'])->toBe('E♭-Dur')
        ->and(chordFinderKey('Am')['label'])->toBe('A-Moll');
});

test('it derives the diatonic chord qualities', function () {
    $chords = collect(chordFinderKey('C')['chords']);

    expect($chords->pluck('name')->all())->toBe(['C', 'Dm', 'Em', 'F', 'G', 'Am', 'B°'])
        ->and($chords->pluck('number')->all())->toBe(['1', '2m', '3m', '4', '5', '6m', '7°'])
        ->and($chords->pluck('quality')->all())->toBe(['major', 'minor', 'minor', 'major', 'major', 'minor', 'diminished']);
});

test('it derives the diatonic seventh chords', function () {
    $sevenths = collect(chordFinderKey('C')['sevenths']);

    expect($sevenths->pluck('name')->all())->toBe(['Cmaj7', 'Dm7', 'Em7', 'Fmaj7', 'G7', 'Am7', 'Bm7♭5'])
        ->and($sevenths->pluck('number')->all())->toBe(['1maj7', '2m7', '3m7', '4maj7', '5⁷', '6m7', '7m7♭5'])
        ->and($sevenths->every(fn (array $chord) => count($chord['notes']) === 4))->toBeTrue();
});

test('it derives the chords of natural minor keys', function () {
    $chords = collect(chordFinderKey('Am')['chords']);

    expect($chords->pluck('name')->all())->toBe(['Am', 'B°', 'C', 'Dm', 'Em', 'F', 'G'])
        ->and($chords->pluck('number')->all())->toBe(['1m', '2°', '3', '4m', '5m', '6', '7']);
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
        ->flatMap(fn (array $key) => [...$key['chords'], ...$key['sevenths']])
        ->flatMap(fn (array $chord) => collect($chord['notes'])->pluck('semitone'));

    expect($semitones->min())->toBeGreaterThanOrEqual(0)
        ->and($semitones->max())->toBeLessThanOrEqual(23);
});

test('it offers progressions for major and minor keys', function () {
    $progressions = (new BuildChordFinderDataAction)->progressions();

    expect($progressions['major'][0])->toBe(['label' => 'Pop-Klassiker', 'degrees' => [1, 5, 6, 4]])
        ->and(collect($progressions['major'])->pluck('degrees')->flatten()->unique()->every(fn (int $degree) => $degree >= 1 && $degree <= 7))->toBeTrue()
        ->and($progressions['minor'])->not->toBeEmpty();
});
