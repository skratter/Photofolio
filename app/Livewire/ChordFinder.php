<?php

namespace App\Livewire;

use App\Actions\BuildChordFinderDataAction;
use App\Actions\IdentifyChordAction;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Renderless;
use Livewire\Component;

#[Layout('layouts.public')]
class ChordFinder extends Component
{
    /**
     * Bestimmt den Akkord zu den angeklickten Tasten (Halbtöne ab dem C der
     * ersten Oktave). Gibt das Ergebnis direkt an den Browser zurück, ohne die
     * Seite neu zu rendern.
     *
     * @param  array<int, mixed>  $semitones
     * @return array<string, mixed>
     */
    #[Renderless]
    public function identify(array $semitones): array
    {
        $validated = Validator::make(
            ['semitones' => $semitones],
            ['semitones' => ['required', 'array', 'max:24'], 'semitones.*' => ['integer', 'between:0,23']],
        )->validate();

        return app(IdentifyChordAction::class)->execute(array_values(array_map(intval(...), $validated['semitones'])));
    }

    public function render(): View
    {
        $action = app(BuildChordFinderDataAction::class);

        return view('livewire.chord-finder', [
            'keys' => $action->execute(),
            'progressions' => $action->progressions(),
        ])->title('Akkordfinder');
    }
}
