<?php

use App\Models\Assessment;
use App\Models\Presentation;
use App\Models\Question;
use App\Models\Answer;
use App\Models\Attempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('orders public presentation attempts by created_at', function () {
    $assessment = Assessment::factory()->create(['password' => 'orderpass', 'active' => true]);
    $question = Question::factory()->for($assessment)->create();
    $a1 = Answer::factory()->for($question)->create(['correct' => false]);
    $a2 = Answer::factory()->for($question)->create(['correct' => true]);

    $presentation = Presentation::factory()->for($assessment)->create(['user_id' => 'student-order']);

    Attempt::factory()->for($presentation)->create([
        'answer_id' => $a2->id,
        'created_at' => Carbon::parse('2024-01-01 10:00:00'),
    ]);
    Attempt::factory()->for($presentation)->create([
        'answer_id' => $a1->id,
        'created_at' => Carbon::parse('2024-01-01 09:00:00'),
    ]);

    $payload = $this->getJson('/api/presentations/store/orderpass/student-order')
        ->assertOk()
        ->json();

    $attempts = $payload['attempts'] ?? [];
    expect($attempts)->toHaveCount(2);
    expect($attempts[0]['answer_id'])->toBe($a1->id);
    expect($attempts[1]['answer_id'])->toBe($a2->id);
});
