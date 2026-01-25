<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('does not reuse cached user list across admins', function () {
    $adminA = User::factory()->create(['role' => 'admin']);
    $adminB = User::factory()->create(['role' => 'admin']);
    $editor = User::factory()->create(['role' => 'editor']);

    $payloadA = $this->actingAs($adminA, 'web')
        ->getJson('/api/user-management/users')
        ->assertOk()
        ->json();

    expect(collect($payloadA)->pluck('id')->all())->toContain($editor->id);

    $payloadB = $this->actingAs($adminB, 'web')
        ->getJson('/api/user-management/users')
        ->assertOk()
        ->json();

    expect(collect($payloadB)->pluck('id')->all())->toContain($editor->id);
});
