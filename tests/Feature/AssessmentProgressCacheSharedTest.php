<?php

use App\Models\Assessment;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('serves shared cached assessment progress for different users', function () {
    Cache::flush();

    $owner = User::factory()->create();
    $other = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    Question::factory()->for($assessment)->create();

    $payloadOwner = $this->actingAs($owner, 'web')
        ->getJson("/api/assessment/attempts/{$assessment->id}")
        ->assertOk()
        ->json();

    $payloadOther = $this->actingAs($other, 'web')
        ->getJson("/api/assessment/attempts/{$assessment->id}")
        ->assertOk()
        ->json();

    expect($payloadOther)->toBe($payloadOwner);
});
