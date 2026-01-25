<?php

namespace Database\Factories;

use App\Models\Answer;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnswerFactory extends Factory
{
    protected $model = Answer::class;

    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'answer_text' => $this->faker->sentence(),
            'feedback' => $this->faker->optional()->sentence(),
            'correct' => false,
            'sequence' => $this->faker->numberBetween(1, 4),
        ];
    }
}
