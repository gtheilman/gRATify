<?php

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('serves cached empty assessment index payload', function () {
    Cache::flush();

    $admin = User::factory()->create(['role' => 'admin']);

    Cache::put("assessments.index.{$admin->id}.admin", [], 2);

    $this->actingAs($admin, 'web')
        ->getJson('/api/assessments')
        ->assertOk()
        ->assertExactJson([]);
});
