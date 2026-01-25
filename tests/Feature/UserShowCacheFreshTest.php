<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('returns updated user data on a fresh request after cache clear', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $target = User::factory()->create(['name' => 'Original']);

    $this->actingAs($admin, 'web')
        ->getJson("/api/user-management/users/{$target->id}")
        ->assertOk();

    $target->name = 'Updated';
    $target->save();

    Cache::flush();

    $fresh = $this->actingAs($admin, 'web')
        ->getJson("/api/user-management/users/{$target->id}")
        ->assertOk()
        ->json();

    expect($fresh['name'])->toBe('Updated');
});
