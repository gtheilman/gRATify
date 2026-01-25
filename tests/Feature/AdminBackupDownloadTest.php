<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

it('rejects non-admins for backup download', function () {
    $user = User::factory()->create(['role' => 'editor']);

    $this->actingAs($user, 'web')
        ->getJson('/api/admin/backup/download')
        ->assertStatus(Response::HTTP_FORBIDDEN);
});

it('returns error when dump tools are missing', function () {
    $user = User::factory()->create(['role' => 'admin']);

    config([
        'database.default' => 'sqlite',
        'database.connections.sqlite.database' => ':memory:',
    ]);

    $this->actingAs($user, 'web')
        ->getJson('/api/admin/backup/download')
        ->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR)
        ->assertJsonStructure(['message']);
});

it('returns error when sqlite database path is invalid', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $missingPath = sys_get_temp_dir() . '/gratify-missing-' . uniqid('', true) . '.sqlite';

    config([
        'database.default' => 'sqlite',
        'database.connections.sqlite.database' => $missingPath,
    ]);

    $this->actingAs($user, 'web')
        ->getJson('/api/admin/backup/download')
        ->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR)
        ->assertJsonStructure(['message']);
});
