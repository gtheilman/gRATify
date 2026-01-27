<?php

use App\Models\Answer;
use App\Models\Appeal;
use App\Models\Assessment;
use App\Models\Attempt;
use App\Models\Presentation;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('includes appeals in score-by-assessment payload', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->create(['user_id' => $owner->id]);
    $question = Question::factory()->for($assessment)->create();
    $answer = Answer::factory()->for($question)->create(['correct' => true]);
    $presentation = Presentation::factory()->for($assessment)->create();

    Attempt::factory()->for($presentation)->create(['answer_id' => $answer->id]);

    $appeal = Appeal::factory()->create([
        'presentation_id' => $presentation->id,
        'question_id' => $question->id,
        'body' => 'We picked this for a reason.',
    ]);

    $response = $this->actingAs($owner, 'web')
        ->getJson("/api/presentations/score-by-assessment-id/{$assessment->id}")
        ->assertOk()
        ->json();

    expect($response)->toBeArray();
    $appeals = data_get($response, '0.assessment.questions.0.appeals');
    expect($appeals)->toBeArray();
    expect(collect($appeals)->pluck('id'))->toContain($appeal->id);
    expect(collect($appeals)->pluck('body'))->toContain($appeal->body);
});
