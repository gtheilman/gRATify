<?php

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('caches list-assessments-by-user payload for a short ttl', function () {
    Cache::flush();
    Cache::spy();

    $admin = User::factory()->create(['role' => 'admin']);
    $owner = User::factory()->create();
    Assessment::factory()->for($owner, 'user')->create();
    config(['grat.admin_id' => $admin->id]);

    $this->actingAs($admin, 'web')
        ->getJson("/api/list-assessments-by-user/{$owner->id}")
        ->assertOk();

    Cache::shouldHaveReceived('put')->atLeast()->once();
});

it('serves cached list-assessments-by-user payload on repeat requests', function () {
    Cache::flush();

    $admin = User::factory()->create(['role' => 'admin']);
    $owner = User::factory()->create();
    Assessment::factory()->for($owner, 'user')->create();
    config(['grat.admin_id' => $admin->id]);

    $first = $this->actingAs($admin, 'web')
        ->getJson("/api/list-assessments-by-user/{$owner->id}")
        ->assertOk()
        ->json();

    $second = $this->actingAs($admin, 'web')
        ->getJson("/api/list-assessments-by-user/{$owner->id}")
        ->assertOk()
        ->json();

    expect($second)->toBe($first);
});
