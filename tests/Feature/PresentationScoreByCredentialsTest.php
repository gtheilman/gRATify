<?php

use App\Models\Answer;
use App\Models\Assessment;
use App\Models\Attempt;
use App\Models\Presentation;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('scores a presentation by credentials and returns numeric json', function () {
    $assessment = Assessment::factory()->create([
        'password' => 'pw-123',
        'active' => true,
    ]);

    $question = Question::factory()->for($assessment)->create(['sequence' => 1]);
    $correctAnswer = Answer::factory()->for($question)->create(['correct' => true]);

    $presentation = Presentation::factory()->for($assessment)->create([
        'user_id' => 'learner',
    ]);

    Attempt::factory()->create([
        'presentation_id' => $presentation->id,
        'answer_id' => $correctAnswer->id,
    ]);

    $response = $this->getJson('/api/presentations/score-by-credentials/pw-123/learner')
        ->assertOk();

    expect($response->json())->toBe(100);
});

it('returns 404 when presentation not found for credentials', function () {
    $assessment = Assessment::factory()->create([
        'password' => 'pw-404',
        'active' => true,
    ]);
    // No presentation for this user
    $this->getJson('/api/presentations/score-by-credentials/pw-404/missing-user')
        ->assertStatus(404)
        ->assertJson(['message' => 'Not found']);
});
