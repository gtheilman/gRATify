<?php

use App\Models\Assessment;
use App\Models\Presentation;
use App\Models\Question;
use App\Models\Answer;
use App\Models\Attempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns scored presentations payload for score-by-assessment', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $question = Question::factory()->for($assessment)->create();
    $correct = Answer::factory()->for($question)->create(['correct' => true]);
    $presentation = Presentation::factory()->for($assessment)->create();
    Attempt::factory()->for($presentation)->create(['answer_id' => $correct->id]);

    $payload = $this->actingAs($owner, 'web')
        ->getJson("/api/presentations/score-by-assessment-id/{$assessment->id}")
        ->assertOk()
        ->json();

    expect($payload)->toBeArray();
    expect($payload[0])->toHaveKeys(['id', 'assessment_id', 'user_id', 'score', 'assessment']);
});
