<?php

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('returns updated list-assessments-by-user after cache clear', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $owner = User::factory()->create();
    config(['grat.admin_id' => $admin->id]);

    $this->actingAs($admin, 'web')
        ->getJson("/api/list-assessments-by-user/{$owner->id}")
        ->assertOk();

    Assessment::factory()->for($owner, 'user')->create(['title' => 'Added']);

    Cache::flush();

    $fresh = $this->actingAs($admin, 'web')
        ->getJson("/api/list-assessments-by-user/{$owner->id}")
        ->assertOk()
        ->json();

    expect(collect($fresh)->pluck('title')->all())->toContain('Added');
});
