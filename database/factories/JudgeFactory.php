<?php

namespace Database\Factories;

use App\Models\Judge;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Judge>
 */
class JudgeFactory extends Factory
{
    protected $model = Judge::class;

    public function definition()
    {
        return [
            // Creates a new user for this judge
            'user_id' => User::factory(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
