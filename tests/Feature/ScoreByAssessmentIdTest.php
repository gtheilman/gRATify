<?php

use App\Models\Answer;
use App\Models\Assessment;
use App\Models\Attempt;
use App\Models\Presentation;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('scores assessments with attempts and returns non-zero totals', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();

    $question = Question::factory()->for($assessment)->create(['sequence' => 1]);
    $correctAnswer = Answer::factory()->for($question)->create([
        'correct' => true,
        'sequence' => 1,
        'answer_text' => 'Correct',
    ]);
    Answer::factory()->for($question)->create(['correct' => false, 'sequence' => 2]);

    $presentation = Presentation::factory()->for($assessment)->create(['user_id' => 'Team 1']);
    Attempt::factory()->for($presentation)->create(['answer_id' => $correctAnswer->id]);

    $response = $this->actingAs($owner)
        ->getJson("/api/presentations/score-by-assessment-id/{$assessment->id}");

    $response->assertOk();
    $payload = $response->json();
    expect($payload)->toBeArray();
    expect($payload[0]['score'])->toBeGreaterThan(0);
    expect($payload[0]['assessment']['questions'][0]['score'])->toBeGreaterThan(0);
    expect($payload[0]['assessment']['questions'][0]['attempts'][0]['answer']['answer_text'])->toBe('Correct');
});

it('rejects invalid scoring schemes', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $question = Question::factory()->for($assessment)->create(['sequence' => 1]);
    Answer::factory()->for($question)->create(['correct' => true, 'sequence' => 1]);
    Presentation::factory()->for($assessment)->create();

    $this->actingAs($owner, 'web')
        ->getJson("/api/presentations/score-by-assessment-id/{$assessment->id}?scheme=bogus")
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'invalid_scheme')
        ->assertJsonPath('error.message', 'Invalid scoring scheme');
});

it('scores linear scheme based on answer count', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $question = Question::factory()->for($assessment)->create(['sequence' => 1]);

    $answers = Answer::factory()->for($question)->count(5)->create([
        'correct' => false,
    ]);
    $answers->each(function ($answer, $index) {
        $answer->update(['sequence' => $index + 1]);
    });
    $answers[4]->update(['correct' => true]);

    $presentation = Presentation::factory()->for($assessment)->create(['user_id' => 'Team 1']);
    Attempt::factory()->for($presentation)->create(['answer_id' => $answers[0]->id]);
    Attempt::factory()->for($presentation)->create(['answer_id' => $answers[1]->id]);
    Attempt::factory()->for($presentation)->create(['answer_id' => $answers[4]->id]);

    $response = $this->actingAs($owner, 'web')
        ->getJson("/api/presentations/score-by-assessment-id/{$assessment->id}?scheme=linear-decay")
        ->assertOk();

    expect($response->json('0.score'))->toBe(60);
});

it('scores linear decay with zeros when correct is last', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $question = Question::factory()->for($assessment)->create(['sequence' => 1]);

    $answers = Answer::factory()->for($question)->count(4)->create([
        'correct' => false,
    ]);
    $answers->each(function ($answer, $index) {
        $answer->update(['sequence' => $index + 1]);
    });
    $answers[3]->update(['correct' => true]);

    $presentation = Presentation::factory()->for($assessment)->create(['user_id' => 'Team 2']);
    Attempt::factory()->for($presentation)->create(['answer_id' => $answers[0]->id]);
    Attempt::factory()->for($presentation)->create(['answer_id' => $answers[1]->id]);
    Attempt::factory()->for($presentation)->create(['answer_id' => $answers[2]->id]);
    Attempt::factory()->for($presentation)->create(['answer_id' => $answers[3]->id]);

    $response = $this->actingAs($owner, 'web')
        ->getJson("/api/presentations/score-by-assessment-id/{$assessment->id}?scheme=linear-decay-with-zeros")
        ->assertOk();

    expect($response->json('0.score'))->toBe(0);
});
