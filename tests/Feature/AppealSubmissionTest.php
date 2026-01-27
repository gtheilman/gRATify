<?php

use App\Models\Answer;
use App\Models\Appeal;
use App\Models\Assessment;
use App\Models\Attempt;
use App\Models\Presentation;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('stores an appeal after all correct answers are found', function () {
    $assessment = Assessment::factory()->create(['appeals_open' => true]);
    $question = Question::factory()->for($assessment)->create();
    $answer = Answer::factory()->for($question)->create(['correct' => true]);
    $presentation = Presentation::factory()->for($assessment)->create();

    Attempt::factory()->for($presentation)->create([
        'answer_id' => $answer->id,
    ]);

    $payload = [
        'presentation_id' => $presentation->id,
        'question_id' => $question->id,
        'body' => 'We believe option A is more precise.',
    ];

    $this->postJson('/api/appeals', $payload)
        ->assertCreated()
        ->assertJsonFragment(['question_id' => $question->id]);

    $this->assertDatabaseHas('appeals', [
        'presentation_id' => $presentation->id,
        'question_id' => $question->id,
        'body' => $payload['body'],
    ]);
});

it('rejects appeals when not all correct answers are found', function () {
    $assessment = Assessment::factory()->create(['appeals_open' => true]);
    $question = Question::factory()->for($assessment)->create();
    $presentation = Presentation::factory()->for($assessment)->create();

    $payload = [
        'presentation_id' => $presentation->id,
        'question_id' => $question->id,
        'body' => 'Not ready yet.',
    ];

    $this->postJson('/api/appeals', $payload)
        ->assertStatus(409)
        ->assertJsonPath('error.code', 'appeals_not_ready');
});

it('rejects appeals when submissions are closed', function () {
    $assessment = Assessment::factory()->create(['appeals_open' => false]);
    $question = Question::factory()->for($assessment)->create();
    $answer = Answer::factory()->for($question)->create(['correct' => true]);
    $presentation = Presentation::factory()->for($assessment)->create();

    Attempt::factory()->for($presentation)->create([
        'answer_id' => $answer->id,
    ]);

    $payload = [
        'presentation_id' => $presentation->id,
        'question_id' => $question->id,
        'body' => 'Please reconsider.',
    ];

    $this->postJson('/api/appeals', $payload)
        ->assertStatus(423)
        ->assertJsonPath('error.code', 'locked');
});

it('rejects duplicate appeals', function () {
    $assessment = Assessment::factory()->create(['appeals_open' => true]);
    $question = Question::factory()->for($assessment)->create();
    $answer = Answer::factory()->for($question)->create(['correct' => true]);
    $presentation = Presentation::factory()->for($assessment)->create();

    Attempt::factory()->for($presentation)->create([
        'answer_id' => $answer->id,
    ]);

    Appeal::factory()->create([
        'presentation_id' => $presentation->id,
        'question_id' => $question->id,
        'body' => 'Existing appeal.',
    ]);

    $payload = [
        'presentation_id' => $presentation->id,
        'question_id' => $question->id,
        'body' => 'Duplicate appeal.',
    ];

    $this->postJson('/api/appeals', $payload)
        ->assertStatus(409)
        ->assertJsonPath('error.code', 'locked');
});
