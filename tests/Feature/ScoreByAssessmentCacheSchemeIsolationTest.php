<?php

use App\Models\Assessment;
use App\Models\Presentation;
use App\Models\Question;
use App\Models\Answer;
use App\Models\Attempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;

uses(RefreshDatabase::class);

it('caches score-by-assessment separately per scheme', function () {
    Cache::flush();
    Cache::spy();

    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $question = Question::factory()->for($assessment)->create();

    $wrong = Answer::factory()->for($question)->create(['correct' => false]);
    $correct = Answer::factory()->for($question)->create(['correct' => true]);
    Answer::factory()->for($question)->count(2)->create(['correct' => false]);

    $presentation = Presentation::factory()->for($assessment)->create();
    Attempt::factory()->for($presentation)->create(['answer_id' => $wrong->id]);
    Attempt::factory()->for($presentation)->create(['answer_id' => $correct->id]);

    $this->actingAs($owner, 'web')
        ->getJson("/api/presentations/score-by-assessment-id/{$assessment->id}?scheme=linear-decay")
        ->assertOk();

    $this->actingAs($owner, 'web')
        ->getJson("/api/presentations/score-by-assessment-id/{$assessment->id}?scheme=linear-decay-with-zeros")
        ->assertOk();

    Cache::shouldHaveReceived('put')
        ->with("presentations.score-by-assessment.{$assessment->id}.linear-decay.{$owner->id}", Mockery::any(), 2)
        ->atLeast()->once();

    Cache::shouldHaveReceived('put')
        ->with("presentations.score-by-assessment.{$assessment->id}.linear-decay-with-zeros.{$owner->id}", Mockery::any(), 2)
        ->atLeast()->once();
});
