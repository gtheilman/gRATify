<?php

use App\Models\Assessment;
use App\Models\Presentation;
use App\Models\Question;
use App\Models\Answer;
use App\Models\Attempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('caches score-by-assessment payload for a short ttl', function () {
    Cache::flush();
    Cache::spy();

    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $question = Question::factory()->for($assessment)->create();
    $correct = Answer::factory()->for($question)->create(['correct' => true]);
    $presentation = Presentation::factory()->for($assessment)->create();
    Attempt::factory()->for($presentation)->create(['answer_id' => $correct->id]);

    $this->actingAs($owner, 'web')
        ->getJson("/api/presentations/score-by-assessment-id/{$assessment->id}")
        ->assertOk();

    Cache::shouldHaveReceived('put')->atLeast()->once();
});

it('serves cached score-by-assessment payload on repeat requests', function () {
    Cache::flush();

    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $question = Question::factory()->for($assessment)->create();
    $correct = Answer::factory()->for($question)->create(['correct' => true]);
    $presentation = Presentation::factory()->for($assessment)->create();
    Attempt::factory()->for($presentation)->create(['answer_id' => $correct->id]);

    $first = $this->actingAs($owner, 'web')
        ->getJson("/api/presentations/score-by-assessment-id/{$assessment->id}")
        ->assertOk()
        ->json();

    $second = $this->actingAs($owner, 'web')
        ->getJson("/api/presentations/score-by-assessment-id/{$assessment->id}")
        ->assertOk()
        ->json();

    expect($second)->toBe($first);
});
