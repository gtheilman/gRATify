<?php

use App\Models\Assessment;
use App\Models\User;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Question update renumber tests
 *
 * Ensures sequences remain contiguous after updating a question's sequence.
 */
uses(RefreshDatabase::class);

it('renumbers questions after a sequence update', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();

    $q1 = Question::factory()->for($assessment)->create(['sequence' => 1]);
    $q2 = Question::factory()->for($assessment)->create(['sequence' => 2]);
    $q3 = Question::factory()->for($assessment)->create(['sequence' => 3]);

    // Move the third question up by setting a lower sequence
    $this->actingAs($owner)
        ->putJson("/api/questions/{$q3->id}", [
            'id' => $q3->id,
            'title' => $q3->title,
            'stem' => $q3->stem,
            'sequence' => 1,
        ])
        ->assertStatus(200);

    $sequences = Question::whereIn('id', [$q1->id, $q2->id, $q3->id])
        ->orderBy('sequence')
        ->pluck('sequence')
        ->all();

    expect($sequences)->toBe([1, 2, 3]);
});
