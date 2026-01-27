<?php

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows owners to toggle appeals open state', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->create([
        'user_id' => $owner->id,
        'appeals_open' => true,
    ]);

    $this->actingAs($owner, 'web')
        ->patchJson("/api/assessments/{$assessment->id}/appeals", [
            'appeals_open' => false,
        ])
        ->assertOk()
        ->assertJsonPath('appeals_open', false);

    $this->assertDatabaseHas('assessments', [
        'id' => $assessment->id,
        'appeals_open' => 0,
    ]);
});

it('rejects appeals toggle for non-owners', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $assessment = Assessment::factory()->create([
        'user_id' => $owner->id,
        'appeals_open' => true,
    ]);

    $this->actingAs($other, 'web')
        ->patchJson("/api/assessments/{$assessment->id}/appeals", [
            'appeals_open' => false,
        ])
        ->assertStatus(403);
});
