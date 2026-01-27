<?php

namespace Database\Factories;

use App\Models\Appeal;
use App\Models\Presentation;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppealFactory extends Factory
{
    protected $model = Appeal::class;

    public function definition(): array
    {
        return [
            'presentation_id' => Presentation::factory(),
            'question_id' => Question::factory(),
            'body' => $this->faker->paragraph(),
        ];
    }
}
