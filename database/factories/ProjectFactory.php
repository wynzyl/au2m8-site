<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => Str::limit(fake()->catchPhrase(), 100, ''),
            'description' => fake()->sentence(),
            'image_path' => 'projects/'.Str::random(40).'.webp',
            'tags' => fake()->randomElements(
                ['Laravel', 'Livewire', 'PostgreSQL', 'React', 'NextJS', 'Tailwind CSS', 'Vue'],
                fake()->numberBetween(1, 3),
            ),
            'is_published' => true,
            'sort_order' => fake()->numberBetween(3, 100),
        ];
    }

    /**
     * Indicate that the project is hidden from the public Projects page.
     */
    public function unpublished(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_published' => false,
        ]);
    }
}
