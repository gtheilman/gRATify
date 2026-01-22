<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns auth me payload with force_password_reset false for normal users', function () {
    $user = User::factory()->create([
        'email' => 'user@example.com',
    ]);

    $this->actingAs($user, 'web')
        ->getJson('/api/auth/me')
        ->assertOk()
        ->assertJsonPath('id', $user->id)
        ->assertJsonPath('force_password_reset', false);
});

it('returns force_password_reset true for seeded admin credentials', function () {
    $seededAdminHash = '$2y$12$Lar5T5y8docuOFsdx98FRevUlRMZRP/40zpowaLJHz2ZtN9b/pww2';
    $user = User::firstWhere('email', 'admin@example.com')
        ?? User::factory()->create(['email' => 'admin@example.com']);
    User::whereKey($user->id)->update(['password' => $seededAdminHash]);
    $user->refresh();

    $this->actingAs($user, 'web')
        ->getJson('/api/auth/me')
        ->assertOk()
        ->assertJsonPath('id', $user->id)
        ->assertJsonPath('force_password_reset', true);
});
