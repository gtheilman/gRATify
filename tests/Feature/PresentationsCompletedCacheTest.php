<?php

use App\Models\Assessment;
use App\Models\Presentation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('caches completed presentations list for a short ttl', function () {
    Cache::flush();
    Cache::spy();

    $admin = User::factory()->create(['role' => 'admin']);
    $owner = User::factory()->create();
    config(['grat.admin_id' => $admin->id]);

    $assessment = Assessment::factory()->for($owner, 'user')->create();
    Presentation::factory()->for($assessment)->create();

    $this->actingAs($admin, 'web')
        ->getJson('/api/presentations/completed')
        ->assertOk();

    Cache::shouldHaveReceived('put')->atLeast()->once();
});

it('serves cached completed presentations on repeat requests', function () {
    Cache::flush();

    $admin = User::factory()->create(['role' => 'admin']);
    $owner = User::factory()->create();
    config(['grat.admin_id' => $admin->id]);

    $assessment = Assessment::factory()->for($owner, 'user')->create();
    Presentation::factory()->for($assessment)->create();

    $first = $this->actingAs($admin, 'web')
        ->getJson('/api/presentations/completed')
        ->assertOk()
        ->json();

    $second = $this->actingAs($admin, 'web')
        ->getJson('/api/presentations/completed')
        ->assertOk()
        ->json();

    expect($second)->toBe($first);
});
