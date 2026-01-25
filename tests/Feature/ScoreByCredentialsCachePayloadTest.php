<?php

use App\Models\Assessment;
use App\Models\Presentation;
use App\Models\Question;
use App\Models\Answer;
use App\Models\Attempt;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns numeric score payload for score-by-credentials', function () {
    $assessment = Assessment::factory()->create(['password' => 'score-num', 'active' => true]);
    $question = Question::factory()->for($assessment)->create();
    $correct = Answer::factory()->for($question)->create(['correct' => true]);
    $presentation = Presentation::factory()->for($assessment)->create(['user_id' => 'student1']);
    Attempt::factory()->for($presentation)->create(['answer_id' => $correct->id]);

    $response = $this->getJson('/api/presentations/score-by-credentials/score-num/student1')
        ->assertOk();

    expect(is_numeric($response->json()))->toBeTrue();
});
