<?php

use App\Models\Appeal;
use App\Models\Assessment;
use App\Models\Presentation;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns appeals in the public presentation payload', function () {
    $assessment = Assessment::factory()->create(['password' => 'abc123']);
    $question = Question::factory()->for($assessment)->create();
    $presentation = Presentation::factory()->for($assessment)->create(['user_id' => 'Team 1']);

    $appeal = Appeal::factory()->create([
        'presentation_id' => $presentation->id,
        'question_id' => $question->id,
        'body' => 'We appeal this answer.',
    ]);

    $response = $this->getJson("/api/presentations/store/{$assessment->password}/{$presentation->user_id}")
        ->assertOk()
        ->json();

    $appeals = data_get($response, 'appeals');
    expect($appeals)->toBeArray();
    expect(collect($appeals)->pluck('id'))->toContain($appeal->id);
});

it('includes appeals open state in the public presentation payload', function () {
    $assessment = Assessment::factory()->create([
        'password' => 'appeals-open',
        'appeals_open' => false,
    ]);
    $presentation = Presentation::factory()->for($assessment)->create(['user_id' => 'Team 2']);

    $response = $this->getJson("/api/presentations/store/{$assessment->password}/{$presentation->user_id}")
        ->assertOk()
        ->json();

    expect(data_get($response, 'assessment.appeals_open'))->toBeFalse();
});
