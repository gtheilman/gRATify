<?php

use App\Models\Assessment;
use App\Models\User;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unauthenticated question update tests
 *
 * Ensures anonymous users cannot update questions.
 */
uses(RefreshDatabase::class);

it('returns 401 when unauthenticated users try to update', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $question = Question::factory()->for($assessment)->create(['sequence' => 1]);

    $this->putJson("/api/questions/{$question->id}", [
        'id' => $question->id,
        'stem' => 'Anon edit',
        'title' => $question->title,
    ])->assertStatus(401);

    expect($question->fresh()->stem)->not->toBe('Anon edit');
});
