<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns a plain array of users for admin on /user-management/users', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    User::factory()->count(3)->create();

    $response = $this->actingAs($admin, 'web')->getJson('/api/user-management/users');
    $response->assertOk();

    $payload = json_decode($response->getContent(), true);
    expect($payload)->toBeArray();
    expect($payload)->toHaveCount(5);
    expect($payload[0])->toHaveKeys(['id', 'name', 'username', 'email', 'role', 'assessments_count']);
});
