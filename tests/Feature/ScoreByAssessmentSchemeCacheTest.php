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

it('caches score-by-assessment separately per scheme', function () {
    Cache::flush();

    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $question = Question::factory()->for($assessment)->create();

    $wrong = Answer::factory()->for($question)->create(['correct' => false]);
    $correct = Answer::factory()->for($question)->create(['correct' => true]);
    Answer::factory()->for($question)->count(2)->create(['correct' => false]);

    $presentation = Presentation::factory()->for($assessment)->create();
    Attempt::factory()->for($presentation)->create(['answer_id' => $wrong->id]);
    Attempt::factory()->for($presentation)->create(['answer_id' => $correct->id]);

    $linear = $this->actingAs($owner, 'web')
        ->getJson("/api/presentations/score-by-assessment-id/{$assessment->id}?scheme=linear-decay")
        ->assertOk()
        ->json('0.score');

    $geometric = $this->actingAs($owner, 'web')
        ->getJson("/api/presentations/score-by-assessment-id/{$assessment->id}")
        ->assertOk()
        ->json('0.score');

    expect($linear)->not->toBe($geometric);
});
