<?php

use App\Http\Controllers\Admin\PageAttachmentController;
use App\Http\Controllers\Admin\PhotoVariantController;
use App\Http\Controllers\RobotsTxtController;
use App\Http\Controllers\ScheduleRunController;
use App\Http\Controllers\SecurityTxtController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\WelcomeController;
use App\Livewire\Admin\AlbumManager;
use App\Livewire\Admin\AlbumShow;
use App\Livewire\Admin\Analytics;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\PageManager;
use App\Livewire\Admin\PhotoShow;
use Illuminate\Support\Facades\Route;

Route::get('/', WelcomeController::class)->name('home');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', RobotsTxtController::class)->name('robots');
Route::get('/.well-known/security.txt', SecurityTxtController::class)->name('security-txt');

Route::get('/cron/schedule-run', ScheduleRunController::class)->name('cron.schedule-run');

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');

    Route::get('/analytics', Analytics::class)->name('analytics');

    Route::get('/pages', PageManager::class)->name('pages.index');
    Route::post('/pages/attachments', [PageAttachmentController::class, 'store'])->name('pages.attachments.store');

    Route::get('/albums', AlbumManager::class)->name('albums.index');
    Route::get('/albums/{album}', AlbumShow::class)->name('albums.show');
    Route::get('/albums/{album}/photos/{photo}', PhotoShow::class)->name('albums.photos.show');

    Route::get('/photos/{photo}/thumb', PhotoVariantController::class)
        ->defaults('variant', 'thumb')
        ->name('photos.thumb');
    Route::get('/photos/{photo}/display', PhotoVariantController::class)
        ->defaults('variant', 'display')
        ->name('photos.display');
});
