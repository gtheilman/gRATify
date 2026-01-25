<?php

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Smoke tests for feedback/url/scores pages to ensure routes respond.
 */
uses(RefreshDatabase::class);

it('serves the feedback page for an assessment', function () {
    $user = User::factory()->create();
    $assessment = Assessment::factory()->for($user, 'user')->create();

    $this->actingAs($user)
        ->get("/assessments/{$assessment->id}/feedback")
        ->assertOk();
});

it('serves the password/url page for an assessment', function () {
    $user = User::factory()->create();
    $assessment = Assessment::factory()->for($user, 'user')->create();

    $this->actingAs($user)
        ->get("/assessments/{$assessment->id}/password")
        ->assertOk();
});

it('serves the scores page for an assessment', function () {
    $user = User::factory()->create();
    $assessment = Assessment::factory()->for($user, 'user')->create();

    $this->actingAs($user)
        ->get("/assessments/{$assessment->id}/scores")
        ->assertOk();
});
