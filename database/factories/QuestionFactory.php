<?php

namespace Database\Factories;

use App\Models\Assessment;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        return [
            'assessment_id' => Assessment::factory(),
            'title' => $this->faker->sentence(5),
            'stem' => $this->faker->paragraph(),
            'points_possible' => $this->faker->randomFloat(2, 1, 10),
            'sequence' => $this->faker->numberBetween(1, 20),
        ];
    }
}
