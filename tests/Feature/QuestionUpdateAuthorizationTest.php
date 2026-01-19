<?php

use App\Models\Assessment;
use App\Models\User;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Question update authorization tests
 *
 * Ensures only the assessment owner can edit questions.
 */
uses(RefreshDatabase::class);

it('blocks updates from non-owners', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $question = Question::factory()->for($assessment)->create(['sequence' => 1]);

    $this->actingAs($otherUser)
        ->putJson("/api/questions/{$question->id}", [
            'id' => $question->id,
            'stem' => 'Unauthorized edit',
            'title' => $question->title,
        ])
        ->assertStatus(403);

    expect($question->fresh()->stem)->not->toBe('Unauthorized edit');
});
