<?php

use App\Models\Assessment;
use App\Models\User;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Not-found question delete tests
 *
 * Ensures deleting a missing question returns 404 and leaves existing data intact.
 */
uses(RefreshDatabase::class);

it('returns 404 when the question does not exist', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $question = Question::factory()->for($assessment)->create(['sequence' => 1]);

    $missingId = $question->id + 999;

    $this->actingAs($owner)
        ->deleteJson("/api/questions/{$missingId}")
        ->assertStatus(404);

    expect(Question::find($question->id))->not->toBeNull();
});
