<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns auth me payload required by the admin UI', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'web')
        ->getJson('/api/auth/me')
        ->assertOk()
        ->assertJsonStructure([
            'id',
            'name',
            'email',
            'role',
            'force_password_reset',
        ]);
});
