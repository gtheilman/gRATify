<?php

use App\Models\Assessment;
use App\Models\Presentation;
use App\Models\Question;
use App\Models\Answer;
use App\Models\Attempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('orders progress presentations and attempts by created_at', function () {
    Cache::flush();

    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $question = Question::factory()->for($assessment)->create();
    $a1 = Answer::factory()->for($question)->create(['correct' => false]);
    $a2 = Answer::factory()->for($question)->create(['correct' => true]);

    $p1 = Presentation::factory()->for($assessment)->create(['created_at' => Carbon::parse('2024-01-01 09:00:00')]);
    $p2 = Presentation::factory()->for($assessment)->create(['created_at' => Carbon::parse('2024-01-01 10:00:00')]);

    Attempt::factory()->for($p2)->create([
        'answer_id' => $a2->id,
        'created_at' => Carbon::parse('2024-01-01 10:02:00'),
    ]);
    Attempt::factory()->for($p2)->create([
        'answer_id' => $a1->id,
        'created_at' => Carbon::parse('2024-01-01 10:01:00'),
    ]);

    $payload = $this->actingAs($owner, 'web')
        ->getJson("/api/assessment/attempts/{$assessment->id}")
        ->assertOk()
        ->json();

    $presentations = $payload['presentations'] ?? [];
    expect($presentations)->toHaveCount(2);
    expect($presentations[0]['id'])->toBe($p1->id);
    expect($presentations[1]['id'])->toBe($p2->id);

    $attempts = $presentations[1]['attempts'] ?? [];
    expect($attempts)->toHaveCount(2);
    expect($attempts[0]['answer_id'])->toBe($a1->id);
    expect($attempts[1]['answer_id'])->toBe($a2->id);
});
