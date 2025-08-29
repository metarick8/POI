<?php

namespace Database\Factories;

use App\Models\Motion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Motion>
 */
class MotionFactory extends Factory
{
    protected $model = Motion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    
    public function definition(): array
    {
      return [
            'sentence' => $this->faker->sentence(6, true), // random sentence, 6 words
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
