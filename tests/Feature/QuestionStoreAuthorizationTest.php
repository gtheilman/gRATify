<?php

use App\Models\Assessment;
use App\Models\User;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Question store authorization tests
 *
 * Ensures only the assessment owner can create questions.
 */
uses(RefreshDatabase::class);

it('allows the owner to create a question', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();

    $this->actingAs($owner, 'web')
        ->postJson('/api/questions', [
            'title' => 'New question',
            'stem' => 'New stem',
            'assessment_id' => $assessment->id,
        ])
        ->assertCreated();

    $questions = Question::where('assessment_id', $assessment->id)->orderBy('sequence')->get();
    expect($questions)->toHaveCount(1);
    expect($questions->first()->title)->toBe('New question');
    expect($questions->first()->sequence)->toBe(1);
});

it('blocks non-owners from creating a question', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();

    $this->actingAs($otherUser, 'web')
        ->postJson('/api/questions', [
            'title' => 'Should not create',
            'stem' => 'Should not create',
            'assessment_id' => $assessment->id,
        ])
        ->assertStatus(403);

    $questions = Question::where('assessment_id', $assessment->id)->get();
    expect($questions)->toHaveCount(0);
});
