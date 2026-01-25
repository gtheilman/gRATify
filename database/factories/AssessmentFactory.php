<?php

namespace Database\Factories;

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssessmentFactory extends Factory
{
    protected $model = Assessment::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'user_id' => User::factory(),
            'time_limit' => $this->faker->numberBetween(10, 60),
            'penalty_method' => $this->faker->randomElement(['percent', 'logarithmic']),
            'course' => $this->faker->lexify('COURSE ###'),
            'short_url' => $this->faker->optional()->url(),
            'scheduled_at' => $this->faker->optional()->dateTimeBetween('-1 month', '+1 month'),
            'memo' => $this->faker->sentence(),
            'password' => $this->faker->unique()->bothify('####??'),
            'active' => true,
        ];
    }
}
