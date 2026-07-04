<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Admin\AlbumManager;


Route::view('/', 'pages/welcome')->name('home');

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/albums', AlbumManager::class)->name('albums.index');
});

require __DIR__ . '/settings.php';
