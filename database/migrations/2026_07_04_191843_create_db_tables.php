<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // albums first, WITHOUT the cover_photo_id FK constraint yet -
        // photos doesn't exist at this point, so the FK can't be resolved.
        Schema::create('albums', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('cover_photo_id')->nullable(); // constraint added below, after photos exists
            $table->enum('visibility', ['public', 'private'])->default('public');
            $table->string('password_hash')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_homepage')->default(false);
            $table->timestamps();
        });

        // Now photos can safely reference albums.id
        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('album_id')->constrained()->cascadeOnDelete();

            // User-editable
            $table->string('title')->nullable();
            $table->text('notes')->nullable();

            // File reference
            $table->string('original_filename');
            $table->string('disk_path'); // relative path within storage, e.g. "photos/{id}/original.jpg"
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size'); // bytes, original
            $table->unsignedSmallInteger('width');   // original pixel width
            $table->unsignedSmallInteger('height');  // original pixel height

            // EXIF - dedicated columns for display
            $table->string('camera_make')->nullable();
            $table->string('camera_model')->nullable();
            $table->string('lens')->nullable();
            $table->string('aperture')->nullable();      // e.g. "f/2.8" - string, not float (avoids rounding display issues)
            $table->string('shutter_speed')->nullable(); // e.g. "1/250" - string, same reason
            $table->unsignedInteger('iso')->nullable();
            $table->string('focal_length')->nullable();  // e.g. "50mm"
            $table->timestamp('taken_at')->nullable();   // EXIF DateTimeOriginal

            // GPS - nullable, only present if camera/phone recorded it
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Full raw EXIF dump as fallback/extensibility
            $table->json('exif_raw')->nullable();

            $table->unsignedInteger('sort_order')->default(0);
            $table->enum('visibility', ['public', 'private'])->default('public');

            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        // photos exists now - safe to add the cover_photo_id FK constraint on albums
        Schema::table('albums', function (Blueprint $table) {
            $table->foreign('cover_photo_id')->references('id')->on('photos')->nullOnDelete();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('photo_tag', function (Blueprint $table) {
            $table->foreignId('photo_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['photo_id', 'tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop in reverse dependency order, and drop the cross-referencing FK
        // before dropping albums/photos to avoid FK errors on rollback.
        Schema::table('albums', function (Blueprint $table) {
            $table->dropForeign(['cover_photo_id']);
        });

        Schema::dropIfExists('photo_tag');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('photos');
        Schema::dropIfExists('albums');
    }
};