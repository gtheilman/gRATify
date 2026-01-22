<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('flags seeded admin credentials on login', function () {
    $seededAdminHash = '$2y$12$Lar5T5y8docuOFsdx98FRevUlRMZRP/40zpowaLJHz2ZtN9b/pww2';
    DB::table('users')->updateOrInsert(
        ['email' => 'admin@example.com'],
        [
            'name' => 'Admin User',
            'username' => 'admin',
            'role' => 'admin',
            'password' => $seededAdminHash,
            'created_at' => now(),
            'updated_at' => now(),
        ]
    );
    config(['hashing.bcrypt.rounds' => 12]);

    $this->withCsrfToken()
        ->postJson('/login', [
            'email' => 'admin@example.com',
            'password' => 'admin',
        ])
        ->assertOk()
        ->assertJsonPath('force_password_reset', true);
});
