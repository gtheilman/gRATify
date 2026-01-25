<?php

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('returns updated assessments on a fresh request after cache clear', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'web')
        ->getJson('/api/assessments')
        ->assertOk();

    Assessment::factory()->for($admin, 'user')->create(['title' => 'New Assessment']);

    Cache::flush();

    $fresh = $this->actingAs($admin, 'web')
        ->getJson('/api/assessments')
        ->assertOk()
        ->json();

    expect(collect($fresh)->pluck('title')->all())->toContain('New Assessment');
});
