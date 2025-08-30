<?php

namespace Database\Factories;

use App\Models\Debate;
use App\Models\Judge;
use App\Models\Motion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Debate>
 */
class DebateFactory extends Factory
{
    protected $model = Debate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = [
            'announced',
            'playersConfirmed',
            'debatePreperation',
            'ongoing',
            'finished',
            'cancelled',
            'bugged',
        ];

        return [
            'motion_id' => Motion::factory(),
            'chair_judge_id' => Judge::factory(),
            'start_date' => $this->faker->dateTimeBetween('-1 year', '+1 year')->format('Y-m-d'),
            'start_time' => $this->faker->optional()->time(),
            'type' => $this->faker->randomElement(['onsite', 'online']),
            'status' => $this->faker->randomElement($statuses),
            'filter' => $this->faker->optional()->word(),
        ];
    }
}
