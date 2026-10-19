<?php

namespace Database\Factories;

use App\Models\CompanyProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyProfile>
 */
class CompanyProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'tagline' => fake()->catchPhrase(),
            'description' => fake()->sentence(),
            'founded_year' => fake()->numberBetween(1900, (int) date('Y')),
            'mission' => fake()->sentence(),
            'email' => fake()->companyEmail(),
            'phone' => '+63 (917) 555-'.fake()->numerify('####'),
            'address' => fake()->address(),
        ];
    }
}
