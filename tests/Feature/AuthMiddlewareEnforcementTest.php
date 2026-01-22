<?php

use App\Models\Assessment;
use App\Models\Attempt;
use App\Models\Answer;
use App\Models\Presentation;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns 401 for unauthenticated assessment create', function () {
    $this->postJson('/api/assessments', [
        'title' => 'New Assessment',
        'time_limit' => 10,
        'course' => 'BIO 101',
        'penalty_method' => 'none',
        'memo' => 'memo',
        'active' => true,
    ])->assertStatus(401);
});

it('returns 401 for unauthenticated question create', function () {
    $assessment = Assessment::factory()->create();

    $this->postJson('/api/questions', [
        'title' => 'New question',
        'stem' => 'New stem',
        'assessment_id' => $assessment->id,
    ])->assertStatus(401);
});

it('returns 401 for unauthenticated attempt update', function () {
    $assessment = Assessment::factory()->create();
    $question = Question::factory()->for($assessment)->create();
    $answer = Answer::factory()->for($question)->create();
    $presentation = Presentation::factory()->for($assessment)->create();
    $attempt = Attempt::factory()->create([
        'presentation_id' => $presentation->id,
        'answer_id' => $answer->id,
    ]);

    $this->patchJson("/api/attempts/{$attempt->id}", [
        'presentation_id' => $presentation->id,
        'answer_id' => $answer->id,
        'points' => 5,
    ])->assertStatus(401);
});
