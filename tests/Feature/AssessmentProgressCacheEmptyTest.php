<?php

use App\Models\Assessment;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('caches empty assessment progress payload on repeat requests', function () {
    Cache::flush();
    Cache::spy();

    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    Question::factory()->for($assessment)->create();

    $this->actingAs($owner, 'web')
        ->getJson("/api/assessment/attempts/{$assessment->id}")
        ->assertOk();

    Cache::shouldHaveReceived('put')->atLeast()->once();

    Cache::flush();
    Cache::put("assessment.progress.{$assessment->id}", [], 2);

    $this->actingAs($owner, 'web')
        ->getJson("/api/assessment/attempts/{$assessment->id}")
        ->assertOk()
        ->assertExactJson([]);
});
