<?php

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns a plain array of assessments for admin on /api/assessments', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Assessment::factory()->for($admin, 'user')->count(2)->create();

    $response = $this->actingAs($admin, 'web')->getJson('/api/assessments')
        ->assertOk();

    $payload = $response->json();
    expect($payload)->toBeArray();
    expect($payload)->toHaveCount(2);
    expect($payload[0])->toHaveKeys(['id', 'title', 'course', 'memo', 'active', 'owner_username']);
});
