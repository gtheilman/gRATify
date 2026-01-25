<?php

use App\Models\Assessment;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('does not allow unauthenticated access to assessment progress', function () {
    $assessment = Assessment::factory()->create();
    Question::factory()->for($assessment)->create();

    $this->getJson("/api/assessment/attempts/{$assessment->id}")
        ->assertStatus(401);
});

it('allows authenticated access to assessment progress', function () {
    $user = User::factory()->create();
    $assessment = Assessment::factory()->create();
    Question::factory()->for($assessment)->create();

    $this->actingAs($user, 'web')
        ->getJson("/api/assessment/attempts/{$assessment->id}")
        ->assertOk();
});
