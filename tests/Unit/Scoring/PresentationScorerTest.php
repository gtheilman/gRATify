<?php

use App\Models\Answer;
use App\Models\Assessment;
use App\Models\Attempt;
use App\Models\Presentation;
use App\Models\Question;
use App\Services\Scoring\PresentationScorer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('scores a presentation and attaches sorted attempts to questions', function () {
    $assessment = Assessment::factory()->create();
    $q1 = Question::factory()->for($assessment)->create(['sequence' => 1]);
    $q2 = Question::factory()->for($assessment)->create(['sequence' => 2]);

    $a1 = Answer::factory()->for($q1)->create(['correct' => false]);
    $a2 = Answer::factory()->for($q1)->create(['correct' => true]);
    $a3 = Answer::factory()->for($q2)->create(['correct' => true]);

    $presentation = Presentation::factory()->for($assessment)->create([
        'user_id' => 'learner',
    ]);

    Attempt::factory()->create([
        'presentation_id' => $presentation->id,
        'answer_id' => $a2->id,
        'created_at' => now()->addMinutes(2),
    ]);
    Attempt::factory()->create([
        'presentation_id' => $presentation->id,
        'answer_id' => $a1->id,
        'created_at' => now()->addMinutes(1),
    ]);
    Attempt::factory()->create([
        'presentation_id' => $presentation->id,
        'answer_id' => $a3->id,
        'created_at' => now()->addMinutes(1),
    ]);

    $presentation->load('attempts.answer');
    $assessment->load('questions');

    $scorer = app(PresentationScorer::class);
    $scored = $scorer->score($presentation, $assessment);

    expect($scored->score)->toBeNumeric();
    $questions = $scored->assessment->questions;
    expect($questions)->toHaveCount(2);
    expect($questions->first()->attempts)->toHaveCount(2);
    expect($questions->first()->attempts->first()->answer_id)->toBe($a1->id);
});
