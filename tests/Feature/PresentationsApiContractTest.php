<?php

use App\Models\Answer;
use App\Models\Assessment;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns presentation payload with sorted questions and hidden correctness', function () {
    $assessment = Assessment::factory()->create([
        'password' => 'contract123',
        'active' => true,
    ]);

    $q2 = Question::factory()->for($assessment)->create([
        'stem' => 'Second',
        'sequence' => 2,
    ]);

    $q1 = Question::factory()->for($assessment)->create([
        'stem' => 'First',
        'sequence' => 1,
    ]);

    Answer::factory()->for($q1)->create([
        'answer_text' => 'A',
        'sequence' => 1,
        'correct' => true,
    ]);

    $response = $this->getJson('/api/presentations/store/contract123/student1')
        ->assertCreated()
        ->assertJsonPath('assessment_id', $assessment->id)
        ->json();

    $questions = data_get($response, 'assessment.questions');
    $sequences = collect($questions)->pluck('sequence')->values()->all();
    $sortedSequences = $sequences;
    sort($sortedSequences);

    expect($questions)->toHaveCount(2);
    expect($sortedSequences)->toBe([1, 2]);
    expect(collect($questions)->firstWhere('sequence', 1)['stem'] ?? null)->toBe('First');
    expect(data_get($questions, '0.answers.0'))->not->toHaveKey('correct');
    expect(data_get($response, 'attempts'))->toBeArray();
});

it('returns 404 for unknown presentation password', function () {
    $this->getJson('/api/presentations/store/missing/student1')
        ->assertNotFound();
});

it('returns 403 for inactive assessments', function () {
    Assessment::factory()->create([
        'password' => 'inactive1',
        'active' => false,
    ]);

    $this->getJson('/api/presentations/store/inactive1/student1')
        ->assertStatus(403)
        ->assertJsonPath('status', 'Forbidden');
});
