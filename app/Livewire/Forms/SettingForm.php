<?php

namespace App\Livewire\Forms;

use App\Models\Setting;
use Livewire\Attributes\Validate;
use Livewire\Form;

class SettingForm extends Form
{
    #[Validate('required|string|max:255')]
    public string $site_name = '';

    #[Validate('required|integer|min:1|max:100')]
    public int $homepage_photo_count = 12;

    #[Validate('required|integer|min:1|max:120')]
    public int $homepage_rotate_seconds = 8;

    #[Validate('required|integer|min:1|max:60')]
    public int $slideshow_autoplay_seconds = 3;

    #[Validate('required|integer|min:1|max:200')]
    public int $album_photos_per_page = 30;

    #[Validate('required|integer|min:1|max:6')]
    public int $masonry_columns = 3;

    #[Validate('nullable|url|max:255')]
    public string $social_instagram_url = '';

    #[Validate('nullable|url|max:255')]
    public string $social_linkedin_url = '';

    #[Validate('nullable|string|max:2000')]
    public string $analytics_own_domains = '';

    public function setFromSetting(Setting $setting): void
    {
        $this->site_name = $setting->site_name ?? '';
        $this->homepage_photo_count = $setting->homepage_photo_count;
        $this->homepage_rotate_seconds = $setting->homepage_rotate_seconds;
        $this->slideshow_autoplay_seconds = $setting->slideshow_autoplay_seconds;
        $this->album_photos_per_page = $setting->album_photos_per_page;
        $this->masonry_columns = $setting->masonry_columns;
        $this->social_instagram_url = $setting->social_instagram_url ?? '';
        $this->social_linkedin_url = $setting->social_linkedin_url ?? '';
        $this->analytics_own_domains = $setting->analytics_own_domains ?? '';
    }

    public function update(Setting $setting): void
    {
        $this->validate();

        $setting->update([
            'site_name' => $this->site_name,
            'homepage_photo_count' => $this->homepage_photo_count,
            'homepage_rotate_seconds' => $this->homepage_rotate_seconds,
            'slideshow_autoplay_seconds' => $this->slideshow_autoplay_seconds,
            'album_photos_per_page' => $this->album_photos_per_page,
            'masonry_columns' => $this->masonry_columns,
            'social_instagram_url' => $this->social_instagram_url ?: null,
            'social_linkedin_url' => $this->social_linkedin_url ?: null,
            'analytics_own_domains' => $this->analytics_own_domains ?: null,
        ]);

        Setting::forgetCached();
    }
}
