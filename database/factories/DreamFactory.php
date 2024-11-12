<?php

namespace Database\Factories;

use App\Models\Dream;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dream>
 */
class DreamFactory extends Factory
{
    protected $model = Dream::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(),
            'dream_content' => $this->faker->paragraph(),
            'is_public' => $this->faker->boolean(true),
            'dream_date' => $this->faker->dateTimeBetween('now', '+1 week')->setTime(3, 0),
            'mood_before_sleep' => $this->faker->numberBetween(1, 5),
            'mood_after_waking' => $this->faker->numberBetween(1, 5),
            'intensity' => $this->faker->numberBetween(1, 5),
            'sleep_quality' => $this->faker->numberBetween(1, 5),
            'overall_theme' => $this->faker->optional()->word(),
            'analysis' => $this->faker->paragraph(),
            'sentiment' => $this->faker->randomElement(['positive', 'negative', 'neutral']),
            'sleep_duration' => $this->faker->optional()->randomFloat(2, 0, 12),
            'location' => json_encode([
                'latitude' => $this->faker->latitude(),
                'longitude' => $this->faker->longitude()
            ]),
            'weather' => json_encode([
                'temperature' => $this->faker->numberBetween(-30, 50),
                'condition' => $this->faker->word(),
                'humidity' => $this->faker->numberBetween(0, 100)
            ]),
        ];
    }
}
