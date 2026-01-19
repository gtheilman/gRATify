<?php

use App\Models\Assessment;
use App\Models\User;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Question delete authorization tests
 *
 * Ensures only the assessment owner (or admin) can delete questions.
 */
uses(RefreshDatabase::class);

it('blocks deletes from non-owners', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $question = Question::factory()->for($assessment)->create(['sequence' => 1]);

    $this->actingAs($otherUser)
        ->deleteJson("/api/questions/{$question->id}")
        ->assertStatus(403);

    expect(Question::find($question->id))->not->toBeNull();
});
