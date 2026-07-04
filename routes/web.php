<?php

use App\Livewire\Admin\AlbumManager;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages/welcome')->name('home');

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/albums', AlbumManager::class)->name('albums.index');
});
