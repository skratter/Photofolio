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
            $table->string('site_name')->nullable()->after('id');
            $table->string('social_instagram_url')->nullable()->after('album_photos_per_page');
            $table->string('social_linkedin_url')->nullable()->after('social_instagram_url');
            $table->text('analytics_own_domains')->nullable()->after('social_linkedin_url');
            $table->unsignedInteger('masonry_columns')->default(3)->after('analytics_own_domains');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'site_name',
                'social_instagram_url',
                'social_linkedin_url',
                'analytics_own_domains',
                'masonry_columns',
            ]);
        });
    }
};
