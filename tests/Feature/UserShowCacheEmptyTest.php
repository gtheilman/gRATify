<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('serves cached empty user show payload', function () {
    Cache::flush();

    $admin = User::factory()->create(['role' => 'admin']);
    $target = User::factory()->create();

    Cache::put("user-management.user.{$admin->id}.{$target->id}", [], 2);

    $this->actingAs($admin, 'web')
        ->getJson("/api/user-management/users/{$target->id}")
        ->assertOk()
        ->assertExactJson([]);
});
