<?php

use App\Models\Assessment;
use App\Models\Presentation;
use App\Models\Question;
use App\Models\Answer;
use App\Models\Attempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('caches score-by-credentials payload for a short ttl', function () {
    Cache::flush();
    Cache::spy();

    $assessment = Assessment::factory()->create(['password' => 'scorepass', 'active' => true]);
    $question = Question::factory()->for($assessment)->create();
    $correct = Answer::factory()->for($question)->create(['correct' => true]);
    $presentation = Presentation::factory()->for($assessment)->create(['user_id' => 'student1']);
    Attempt::factory()->for($presentation)->create(['answer_id' => $correct->id]);

    $this->getJson('/api/presentations/score-by-credentials/scorepass/student1')
        ->assertOk();

    Cache::shouldHaveReceived('put')->atLeast()->once();
});

it('serves cached score-by-credentials payload on repeat requests', function () {
    Cache::flush();

    $assessment = Assessment::factory()->create(['password' => 'scorepass2', 'active' => true]);
    $question = Question::factory()->for($assessment)->create();
    $correct = Answer::factory()->for($question)->create(['correct' => true]);
    $presentation = Presentation::factory()->for($assessment)->create(['user_id' => 'student2']);
    Attempt::factory()->for($presentation)->create(['answer_id' => $correct->id]);

    $first = $this->getJson('/api/presentations/score-by-credentials/scorepass2/student2')
        ->assertOk()
        ->json();

    $second = $this->getJson('/api/presentations/score-by-credentials/scorepass2/student2')
        ->assertOk()
        ->json();

    expect($second)->toBe($first);
});
