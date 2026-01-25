<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('returns migration status for admins', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin, 'web')
        ->getJson('/api/admin/migration-status')
        ->assertOk()
        ->json();

    expect($response['ok'])->toBeTrue();
    expect($response['missing'])->toBeArray();
});

it('flags missing migrations for admins', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    DB::table('migrations')->where('migration', '2026_01_25_221000_add_foreign_keys')->delete();

    $response = $this->actingAs($admin, 'web')
        ->getJson('/api/admin/migration-status')
        ->assertOk()
        ->json();

    expect($response['ok'])->toBeFalse();
    expect($response['missing'])->toContain('2026_01_25_221000_add_foreign_keys');
});

it('rejects non-admins', function () {
    $user = User::factory()->create(['role' => 'instructor']);

    $this->actingAs($user, 'web')
        ->getJson('/api/admin/migration-status')
        ->assertStatus(403);
});

it('returns migration status on the public endpoint', function () {
    $response = $this->getJson('/api/migration-status')
        ->assertOk()
        ->json();

    expect($response['ok'])->toBeTrue();
    expect($response['missing'])->toBeArray();
});
