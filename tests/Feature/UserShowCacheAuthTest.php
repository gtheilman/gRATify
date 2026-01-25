<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('does not serve cached user show to non-admins', function () {
    Cache::flush();

    $admin = User::factory()->create(['role' => 'admin']);
    $editor = User::factory()->create(['role' => 'editor']);
    $target = User::factory()->create(['role' => 'editor']);

    $this->actingAs($admin, 'web')
        ->getJson("/api/user-management/users/{$target->id}")
        ->assertOk();

    $this->actingAs($editor, 'web')
        ->getJson("/api/user-management/users/{$target->id}")
        ->assertStatus(403);
});
