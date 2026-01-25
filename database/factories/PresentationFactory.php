<?php

namespace Database\Factories;

use App\Models\Assessment;
use App\Models\Presentation;
use Illuminate\Database\Eloquent\Factories\Factory;

class PresentationFactory extends Factory
{
    protected $model = Presentation::class;

    public function definition(): array
    {
        return [
            'assessment_id' => Assessment::factory(),
            'user_id' => (string) $this->faker->randomNumber(5),
        ];
    }
}
