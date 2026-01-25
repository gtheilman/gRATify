<?php

use App\Models\Assessment;
use App\Models\Presentation;
use App\Models\Question;
use App\Models\Answer;
use App\Models\Attempt;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('does not reuse cached score-by-credentials across users', function () {
    $assessment = Assessment::factory()->create(['password' => 'credpass', 'active' => true]);
    $question = Question::factory()->for($assessment)->create();
    $correct = Answer::factory()->for($question)->create(['correct' => true]);

    $p1 = Presentation::factory()->for($assessment)->create(['user_id' => 'student-a']);
    Attempt::factory()->for($p1)->create(['answer_id' => $correct->id]);

    $p2 = Presentation::factory()->for($assessment)->create(['user_id' => 'student-b']);
    Attempt::factory()->for($p2)->create(['answer_id' => $correct->id]);

    $payloadA = $this->getJson('/api/presentations/score-by-credentials/credpass/student-a')
        ->assertOk()
        ->json();

    $payloadB = $this->getJson('/api/presentations/score-by-credentials/credpass/student-b')
        ->assertOk()
        ->json();

    expect($payloadA)->toBe($payloadB);
});
