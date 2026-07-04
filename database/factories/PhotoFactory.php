<?php

namespace Database\Factories;

use App\Models\Album;
use App\Models\Photo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Photo>
 */
class PhotoFactory extends Factory
{
    protected $model = Photo::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'album_id' => Album::factory(),
            'original_filename' => 'original.jpg',
            'disk_path' => 'photos/placeholder/original.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 123456,
            'width' => 1920,
            'height' => 1080,
            'sort_order' => 0,
        ];
    }

    public function processed(): static
    {
        return $this->state(fn (array $attributes) => [
            'processed_at' => now(),
        ]);
    }
}
