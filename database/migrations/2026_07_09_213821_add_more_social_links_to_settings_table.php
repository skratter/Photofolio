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
        Schema::table('settings', function (Blueprint $table) {
            $table->string('social_facebook_url')->nullable()->after('social_linkedin_url');
            $table->string('social_flickr_url')->nullable()->after('social_facebook_url');
            $table->string('social_x_url')->nullable()->after('social_flickr_url');
            $table->string('social_youtube_url')->nullable()->after('social_x_url');
            $table->string('social_pinterest_url')->nullable()->after('social_youtube_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'social_facebook_url',
                'social_flickr_url',
                'social_x_url',
                'social_youtube_url',
                'social_pinterest_url',
            ]);
        });
    }
};
