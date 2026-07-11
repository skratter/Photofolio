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
            $table->string('favicon_path')->nullable()->after('site_name');
            $table->string('logo_light_path')->nullable()->after('favicon_path');
            $table->string('logo_dark_path')->nullable()->after('logo_light_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['favicon_path', 'logo_light_path', 'logo_dark_path']);
        });
    }
};
