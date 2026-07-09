<?php

namespace App\Providers;

use App\Models\Album;
use App\Models\Page;
use App\Models\PageAttachment;
use App\Models\Setting;
use App\Observers\AlbumObserver;
use App\Observers\PageAttachmentObserver;
use App\Observers\PageObserver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        Album::observe(AlbumObserver::class);
        Page::observe(PageObserver::class);
        PageAttachment::observe(PageAttachmentObserver::class);

        // Two view names because the same file resolves differently depending
        // on how it's rendered: <x-layouts.public> tags resolve it under the
        // "components." prefix, while Livewire's #[Layout('layouts.public')]
        // attribute resolves the raw name via the legacy @component directive.
        View::composer(['components.layouts.public', 'layouts.public'], function ($view): void {
            $view->with([
                'navigationPages' => Page::inNavigation()->orderBy('sort_order')->get(),
                'legalPages' => Page::query()->where('type', 'legal')->published()->get(),
                'siteSettings' => Setting::current(),
            ]);
        });

        // Same two-name situation as above - components.layouts.{app,admin}
        // for the <x-layouts.*> tags, layouts.{app,admin} for Livewire's
        // #[Layout(...)] attribute.
        View::composer(
            ['components.layouts.app', 'layouts.app', 'components.layouts.admin', 'layouts.admin'],
            function ($view): void {
                $view->with('siteSettings', Setting::current());
            }
        );
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(
            fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
