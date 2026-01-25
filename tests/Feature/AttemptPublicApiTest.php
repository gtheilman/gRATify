<?php

use App\Models\Answer;
use App\Models\Assessment;
use App\Models\Attempt;
use App\Models\Presentation;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows unauthenticated clients to record attempts', function () {
    $assessment = Assessment::factory()->create();
    $presentation = Presentation::factory()->for($assessment)->create();
    $question = Question::factory()->for($assessment)->create();
    $answer = Answer::factory()->for($question)->create(['correct' => true]);

    $payload = [
        'presentation_id' => $presentation->id,
        'answer_id' => $answer->id,
        'points' => 5.0,
    ];

    $this->postJson('/api/attempts', $payload)
        ->assertCreated()
        ->assertJsonStructure(['correct', 'alreadyAttempted'])
        ->assertJson([
            'correct' => true,
            'alreadyAttempted' => false,
        ]);

    $this->assertDatabaseHas('attempts', [
        'presentation_id' => $presentation->id,
        'answer_id' => $answer->id,
        'points' => 5.0,
    ]);
});

it('returns attempts with answer correctness on fetch', function () {
    $assessment = Assessment::factory()->create([
        'password' => 'code123',
        'active' => true,
    ]);
    $question = Question::factory()->for($assessment)->create();
    $answer = Answer::factory()->for($question)->create(['correct' => true]);
    $presentation = Presentation::factory()->for($assessment)->create([
        'user_id' => 'user1',
    ]);
    Attempt::factory()->for($presentation)->for($answer)->create();

    $response = $this->getJson('/api/presentations/store/code123/user1')
        ->assertOk()
        ->json();

    $attempts = data_get($response, 'attempts');
    expect($attempts)->toHaveCount(1);
    expect((bool) data_get($attempts, '0.answer.correct'))->toBeTrue();
    expect((bool) data_get($attempts, '0.answer_correct'))->toBeTrue();
});
