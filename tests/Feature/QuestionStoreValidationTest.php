<?php

use App\Models\Assessment;
use App\Models\User;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Question store validation tests
 *
 * Ensures required fields and auth are enforced when creating questions.
 */
uses(RefreshDatabase::class);

it('returns 401 when unauthenticated users try to create', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();

    $this->postJson('/api/questions', [
        'title' => 'New question',
        'stem' => 'New stem',
        'assessment_id' => $assessment->id,
    ])->assertStatus(401);

    expect(Question::where('assessment_id', $assessment->id)->count())->toBe(0);
});

it('rejects creates without a title', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();

    $this->actingAs($owner)
        ->postJson('/api/questions', [
            // title missing
            'stem' => 'New stem',
            'assessment_id' => $assessment->id,
        ])
        ->assertStatus(422);

    expect(Question::where('assessment_id', $assessment->id)->count())->toBe(0);
});

it('rejects creates without a stem', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();

    $this->actingAs($owner)
        ->postJson('/api/questions', [
            'title' => 'New question',
            // stem missing
            'assessment_id' => $assessment->id,
        ])
        ->assertStatus(422);

    expect(Question::where('assessment_id', $assessment->id)->count())->toBe(0);
});

it('rejects creates with invalid assessment_id', function () {
    $owner = User::factory()->create();

    $this->actingAs($owner)
        ->postJson('/api/questions', [
            'title' => 'New question',
            'stem' => 'New stem',
            'assessment_id' => 9999,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['assessment_id']);
});
