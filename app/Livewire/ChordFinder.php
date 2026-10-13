<?php

namespace App\Livewire;

use App\Actions\BuildChordFinderDataAction;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.public')]
class ChordFinder extends Component
{
    public function render(): View
    {
        return view('livewire.chord-finder', [
            'keys' => app(BuildChordFinderDataAction::class)->execute(),
        ])->title('Akkordfinder');
    }
}
