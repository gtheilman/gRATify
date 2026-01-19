<?php

use App\Models\Assessment;
use App\Models\User;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Question update validation tests
 *
 * Ensures required fields are enforced (title, stem).
 */
uses(RefreshDatabase::class);

it('rejects updates without a title', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $question = Question::factory()->for($assessment)->create(['sequence' => 1]);

    $this->actingAs($owner)
        ->putJson("/api/questions/{$question->id}", [
            'id' => $question->id,
            'stem' => 'New stem only',
            // title missing
        ])
        ->assertStatus(422);

    expect($question->fresh()->stem)->not->toBe('New stem only');
});

it('rejects updates without a stem', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $question = Question::factory()->for($assessment)->create(['sequence' => 1]);

    $this->actingAs($owner)
        ->putJson("/api/questions/{$question->id}", [
            'id' => $question->id,
            'title' => 'New title only',
            // stem missing
        ])
        ->assertStatus(422);

    expect($question->fresh()->title)->not->toBe('New title only');
});
