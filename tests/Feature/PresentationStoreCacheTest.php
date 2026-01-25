<?php

use App\Models\Assessment;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('caches public presentation payload for a short ttl', function () {
    Cache::flush();
    Cache::spy();

    $assessment = Assessment::factory()->create([
        'password' => 'cache123',
        'active' => true,
    ]);
    Question::factory()->for($assessment)->create();

    $this->getJson('/api/presentations/store/cache123/student1')
        ->assertStatus(201);

    Cache::shouldHaveReceived('put')->atLeast()->once();
});

it('serves cached payload on repeated requests', function () {
    Cache::flush();

    $assessment = Assessment::factory()->create([
        'password' => 'cache234',
        'active' => true,
    ]);
    Question::factory()->for($assessment)->create();

    $first = $this->getJson('/api/presentations/store/cache234/student1')
        ->assertStatus(201)
        ->json();

    $second = $this->getJson('/api/presentations/store/cache234/student1')
        ->assertOk()
        ->json();

    expect($second)->toBe($first);
});
