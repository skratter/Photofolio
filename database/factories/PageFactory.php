<?php

namespace Database\Factories;

use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    protected $model = Page::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'content' => '<p>'.fake()->paragraph().'</p>',
            'meta_description' => fake()->sentence(),
            'type' => 'standard',
            'status' => 'published',
            'show_in_navigation' => false,
            'sort_order' => 0,
            'published_at' => now(),
        ];
    }

    public function legal(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'legal',
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }
}
