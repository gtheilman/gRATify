<?php

use App\Models\Assessment;
use App\Models\User;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unauthenticated question delete tests
 *
 * Ensures anonymous users cannot delete questions.
 */
uses(RefreshDatabase::class);

it('returns 401 when unauthenticated users try to delete', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $question = Question::factory()->for($assessment)->create(['sequence' => 1]);

    $this->deleteJson("/api/questions/{$question->id}")
        ->assertStatus(401);

    expect(Question::find($question->id))->not->toBeNull();
});
