<?php

namespace Database\Factories;

use App\Models\Page;
use App\Models\PageAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PageAttachment>
 */
class PageAttachmentFactory extends Factory
{
    protected $model = PageAttachment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'page_id' => Page::factory(),
            'disk' => 'public',
            'path' => 'pages/'.fake()->uuid().'.jpg',
            'original_filename' => fake()->word().'.jpg',
            'mime_type' => 'image/jpeg',
            'kind' => 'embedded_image',
            'size' => fake()->numberBetween(1000, 500000),
        ];
    }

    public function download(): static
    {
        return $this->state(fn (array $attributes) => [
            'kind' => 'download',
            'path' => 'pages/'.fake()->uuid().'.pdf',
            'original_filename' => fake()->word().'.pdf',
            'mime_type' => 'application/pdf',
        ]);
    }
}
