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
        // "key" pre-dates the CMS: it was a minimal anchor for view-tracking on
        // pages with no editorial content (e.g. the homepage). Renaming it to
        // "slug" folds that concept into the same table as real CMS pages
        // instead of keeping two similar-but-different "page" ideas around.
        Schema::table('pages', function (Blueprint $table) {
            $table->renameColumn('key', 'slug');
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->string('title')->nullable()->after('id');
            $table->longText('content')->nullable()->after('slug');
            $table->string('meta_description')->nullable()->after('content');
            $table->enum('type', ['standard', 'legal'])->default('standard')->after('meta_description');
            $table->enum('status', ['draft', 'published'])->default('draft')->after('type');
            $table->boolean('show_in_navigation')->default(false)->after('status');
            $table->unsignedInteger('sort_order')->default(0)->after('show_in_navigation');
            $table->timestamp('published_at')->nullable()->after('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn([
                'title',
                'content',
                'meta_description',
                'type',
                'status',
                'show_in_navigation',
                'sort_order',
                'published_at',
            ]);
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->renameColumn('slug', 'key');
        });
    }
};
