<?php

use App\Models\Answer;
use App\Models\Assessment;
use App\Models\Presentation;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('stores attempts in bulk and returns per-item status', function () {
    $assessment = Assessment::factory()->create();
    $presentation = Presentation::factory()->for($assessment)->create();
    $question = Question::factory()->for($assessment)->create();
    $answerOne = Answer::factory()->for($question)->create(['correct' => true]);
    $answerTwo = Answer::factory()->for($question)->create(['correct' => false]);

    $payload = [
        'attempts' => [
            ['presentation_id' => $presentation->id, 'answer_id' => $answerOne->id],
            ['presentation_id' => $presentation->id, 'answer_id' => $answerTwo->id],
        ],
    ];

    $response = $this->postJson('/api/attempts/bulk', $payload)
        ->assertOk()
        ->json();

    $results = collect($response['results'] ?? []);
    expect($results)->toHaveCount(2);
    expect($results->where('status', 'created'))->toHaveCount(2);

    $this->assertDatabaseHas('attempts', [
        'presentation_id' => $presentation->id,
        'answer_id' => $answerOne->id,
    ]);
    $this->assertDatabaseHas('attempts', [
        'presentation_id' => $presentation->id,
        'answer_id' => $answerTwo->id,
    ]);
});

it('is idempotent for repeated bulk submissions', function () {
    $assessment = Assessment::factory()->create();
    $presentation = Presentation::factory()->for($assessment)->create();
    $question = Question::factory()->for($assessment)->create();
    $answer = Answer::factory()->for($question)->create(['correct' => true]);

    $payload = [
        'attempts' => [
            ['presentation_id' => $presentation->id, 'answer_id' => $answer->id],
        ],
    ];

    $this->postJson('/api/attempts/bulk', $payload)->assertOk();
    $this->postJson('/api/attempts/bulk', $payload)
        ->assertOk()
        ->assertJsonFragment(['status' => 'already_attempted']);

    expect(\App\Models\Attempt::count())->toBe(1);
});

it('returns not_found and invalid statuses in bulk', function () {
    $assessment = Assessment::factory()->create();
    $presentation = Presentation::factory()->for($assessment)->create();
    $question = Question::factory()->for($assessment)->create();
    $answer = Answer::factory()->for($question)->create();

    $payload = [
        'attempts' => [
            ['presentation_id' => $presentation->id, 'answer_id' => $answer->id],
            ['presentation_id' => 999999, 'answer_id' => $answer->id],
            ['presentation_id' => $presentation->id, 'answer_id' => 999999],
            ['presentation_id' => null, 'answer_id' => null],
        ],
    ];

    $response = $this->postJson('/api/attempts/bulk', $payload)
        ->assertOk()
        ->json();

    $statuses = collect($response['results'] ?? [])->pluck('status');
    expect($statuses)->toContain('created');
    expect($statuses)->toContain('not_found');
    expect($statuses)->toContain('invalid');
});
