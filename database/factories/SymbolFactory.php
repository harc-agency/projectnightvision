<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Symbol>
 */
class SymbolFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => $this->faker->unique()->numberBetween(1, 10000),
            'symbol_key' => $this->faker->unique()->slug,
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'symbol' => $this->faker->word,
            'interpretation' => $this->faker->paragraph,
            'featured_image' => $this->faker->imageUrl,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
