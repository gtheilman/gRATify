<?php

use App\Models\Assessment;
use App\Models\Presentation;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('caches presentation getAssessment payload for a short ttl', function () {
    Cache::flush();
    Cache::spy();

    $assessment = Assessment::factory()->create();
    Question::factory()->for($assessment)->create();
    $presentation = Presentation::factory()->for($assessment)->create();

    $this->getJson("/api/presentations/getAssessment/{$presentation->id}")
        ->assertOk();

    Cache::shouldHaveReceived('put')->atLeast()->once();
});

it('serves cached presentation getAssessment payload on repeat requests', function () {
    Cache::flush();

    $assessment = Assessment::factory()->create();
    Question::factory()->for($assessment)->create();
    $presentation = Presentation::factory()->for($assessment)->create();

    $first = $this->getJson("/api/presentations/getAssessment/{$presentation->id}")
        ->assertOk()
        ->json();

    $second = $this->getJson("/api/presentations/getAssessment/{$presentation->id}")
        ->assertOk()
        ->json();

    expect($second)->toBe($first);
});
