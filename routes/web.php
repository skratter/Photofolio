<?php

use App\Http\Controllers\Admin\PhotoVariantController;
use App\Http\Controllers\ScheduleRunController;
use App\Livewire\Admin\AlbumManager;
use App\Livewire\Admin\AlbumShow;
use App\Livewire\Admin\PhotoShow;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages/welcome')->name('home');

Route::get('/cron/schedule-run', ScheduleRunController::class)->name('cron.schedule-run');

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
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
