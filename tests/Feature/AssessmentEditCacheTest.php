<?php

use App\Models\Assessment;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('caches assessment edit payload briefly', function () {
    Cache::flush();
    Cache::spy();

    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    Question::factory()->for($assessment)->create();

    $this->actingAs($owner, 'web')
        ->getJson("/api/assessments/{$assessment->id}/edit")
        ->assertOk();

    Cache::shouldHaveReceived('put')->atLeast()->once();
});

it('serves cached assessment edit payload on repeat', function () {
    Cache::flush();

    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    Question::factory()->for($assessment)->create();

    $first = $this->actingAs($owner, 'web')
        ->getJson("/api/assessments/{$assessment->id}/edit")
        ->assertOk()
        ->json();

    $second = $this->actingAs($owner, 'web')
        ->getJson("/api/assessments/{$assessment->id}/edit")
        ->assertOk()
        ->json();

    expect($second)->toBe($first);
});
