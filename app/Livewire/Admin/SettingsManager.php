<?php

namespace App\Livewire\Admin;

use App\Livewire\Forms\SettingForm;
use App\Models\Setting;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.admin')]
class SettingsManager extends Component
{
    use WithFileUploads;

    public SettingForm $form;

    public function mount(): void
    {
        $this->form->setFromSetting(Setting::current());
    }

    #[Computed]
    public function setting(): Setting
    {
        return Setting::current();
    }

    public function save(): void
    {
        $this->form->update(Setting::current());

        Flux::toast(text: 'Einstellungen gespeichert.', variant: 'success');
    }

    public function removeFavicon(): void
    {
        $this->removeBrandingFile('favicon_path');
    }

    public function removeLogoLight(): void
    {
        $this->removeBrandingFile('logo_light_path');
    }

    public function removeLogoDark(): void
    {
        $this->removeBrandingFile('logo_dark_path');
    }

    private function removeBrandingFile(string $column): void
    {
        $setting = Setting::current();

        if ($setting->{$column}) {
            Storage::disk('public')->delete($setting->{$column});
            $setting->update([$column => null]);
            Setting::forgetCached();
        }

        unset($this->setting);
    }

    public function render(): View
    {
        return view('livewire.admin.settings-manager');
    }
}
