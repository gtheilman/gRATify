<?php

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('caches assessment index payload for a short ttl', function () {
    Cache::flush();
    Cache::spy();

    $admin = User::factory()->create(['role' => 'admin']);
    Assessment::factory()->for($admin, 'user')->create();

    $this->actingAs($admin, 'web')
        ->getJson('/api/assessments')
        ->assertOk();

    Cache::shouldHaveReceived('put')->atLeast()->once();
});

it('serves cached assessment index payload on repeat requests', function () {
    Cache::flush();

    $admin = User::factory()->create(['role' => 'admin']);
    Assessment::factory()->for($admin, 'user')->create();

    $first = $this->actingAs($admin, 'web')
        ->getJson('/api/assessments')
        ->assertOk()
        ->json();

    $second = $this->actingAs($admin, 'web')
        ->getJson('/api/assessments')
        ->assertOk()
        ->json();

    expect($second)->toBe($first);
});
