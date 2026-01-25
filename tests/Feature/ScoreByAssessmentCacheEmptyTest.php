<?php

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('serves cached empty score-by-assessment payload', function () {
    Cache::flush();

    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();

    Cache::put("presentations.score-by-assessment.{$assessment->id}.geometric-decay.{$owner->id}", [], 2);

    $this->actingAs($owner, 'web')
        ->getJson("/api/presentations/score-by-assessment-id/{$assessment->id}")
        ->assertOk()
        ->assertExactJson([]);
});
