<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('homepage_photo_count')->default(12);
            $table->unsignedInteger('homepage_rotate_seconds')->default(8);
            $table->unsignedInteger('slideshow_autoplay_seconds')->default(3);
            $table->unsignedInteger('album_photos_per_page')->default(30);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
