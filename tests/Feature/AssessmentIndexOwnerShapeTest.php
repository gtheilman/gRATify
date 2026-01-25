<?php

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns only owner assessments for non-admin users and plain array shape', function () {
    $owner = User::factory()->create(['role' => 'editor']);
    $other = User::factory()->create(['role' => 'editor']);

    Assessment::factory()->for($owner, 'user')->count(2)->create();
    Assessment::factory()->for($other, 'user')->count(1)->create();

    $response = $this->actingAs($owner, 'web')->getJson('/api/assessments')
        ->assertOk();

    $payload = $response->json();
    expect($payload)->toBeArray();
    expect($payload)->toHaveCount(2);
    expect(collect($payload)->pluck('user_id')->unique()->all())->toBe([$owner->id]);
});
