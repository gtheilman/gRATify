<?php

use App\Models\Answer;
use App\Models\Assessment;
use App\Models\Attempt;
use App\Models\Presentation;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

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
        ->assertJsonPath('error.code', 'not_found')
        ->assertJsonPath('error.message', 'Not Found');
});

it('uses attempt order when scoring by credentials', function () {
    config(['scoring.default' => 'linear-decay']);

    $assessment = Assessment::factory()->create([
        'password' => 'pw-linear',
        'active' => true,
    ]);

    $question = Question::factory()->for($assessment)->create(['sequence' => 1]);
    $correct = Answer::factory()->for($question)->create(['correct' => true]);
    $incorrect = Answer::factory()->for($question)->create(['correct' => false]);

    $presentation = Presentation::factory()->for($assessment)->create([
        'user_id' => 'learner-linear',
    ]);

    Attempt::factory()->create([
        'presentation_id' => $presentation->id,
        'answer_id' => $incorrect->id,
        'created_at' => Carbon::parse('2025-01-01 10:00:00'),
    ]);
    Attempt::factory()->create([
        'presentation_id' => $presentation->id,
        'answer_id' => $correct->id,
        'created_at' => Carbon::parse('2025-01-01 10:05:00'),
    ]);

    $response = $this->getJson('/api/presentations/score-by-credentials/pw-linear/learner-linear')
        ->assertOk();

    expect($response->json())->toBe(50);
});
