<?php

namespace App\Livewire\Admin;

use App\Livewire\Forms\SettingForm;
use App\Models\Setting;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class SettingsManager extends Component
{
    public SettingForm $form;

    public function mount(): void
    {
        $this->form->setFromSetting(Setting::current());
    }

    public function save(): void
    {
        $this->form->update(Setting::current());

        Flux::toast(text: 'Einstellungen gespeichert.', variant: 'success');
    }

    public function render(): View
    {
        return view('livewire.admin.settings-manager');
    }
}
