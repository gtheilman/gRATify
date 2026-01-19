<?php

namespace Database\Factories;

use App\Models\Answer;
use App\Models\Attempt;
use App\Models\Presentation;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttemptFactory extends Factory
{
    protected $model = Attempt::class;

    public function definition(): array
    {
        return [
          'presentation_id' => Presentation::factory(),
          'answer_id' => Answer::factory(),
          'points' => $this->faker->optional()->randomFloat(2, 0, 10),
        ];
    }
}
