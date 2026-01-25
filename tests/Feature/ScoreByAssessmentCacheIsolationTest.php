<?php

use App\Models\Assessment;
use App\Models\Presentation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('does not reuse cached score-by-assessment across users', function () {
    Cache::flush();

    $owner = User::factory()->create();
    $other = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    Presentation::factory()->for($assessment)->create();

    $this->actingAs($owner, 'web')
        ->getJson("/api/presentations/score-by-assessment-id/{$assessment->id}")
        ->assertOk();

    $this->actingAs($other, 'web')
        ->getJson("/api/presentations/score-by-assessment-id/{$assessment->id}")
        ->assertStatus(403);
});
