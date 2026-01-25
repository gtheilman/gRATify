<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('serves cached empty list-assessments-by-user payload', function () {
    Cache::flush();

    $admin = User::factory()->create(['role' => 'admin']);
    $owner = User::factory()->create();
    config(['grat.admin_id' => $admin->id]);

    Cache::put("assessments.by-user.{$admin->id}.{$owner->id}", [], 2);

    $this->actingAs($admin, 'web')
        ->getJson("/api/list-assessments-by-user/{$owner->id}")
        ->assertOk()
        ->assertExactJson([]);
});
