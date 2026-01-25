<?php

use App\Models\Assessment;
use App\Models\User;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Question delete renumber tests
 *
 * Ensures sequences are renumbered after a deletion.
 */
uses(RefreshDatabase::class);

it('renumbers remaining questions after delete', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();

    $q1 = Question::factory()->for($assessment)->create(['sequence' => 1]);
    $q2 = Question::factory()->for($assessment)->create(['sequence' => 2]);
    $q3 = Question::factory()->for($assessment)->create(['sequence' => 3]);

    $this->actingAs($owner)
        ->deleteJson("/api/questions/{$q2->id}")
        ->assertNoContent();

    $remaining = Question::whereIn('id', [$q1->id, $q3->id])->orderBy('sequence')->get();

    expect($remaining)->toHaveCount(2);
    expect($remaining[0]->sequence)->toBe(1);
    expect($remaining[1]->sequence)->toBe(2);
});
