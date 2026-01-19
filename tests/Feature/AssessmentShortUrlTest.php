<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('sets short_url to the client path when Bitly is not configured', function () {
    // Ensure no Bitly token is present
    putenv('BITLY_ACCESS_TOKEN');
    config(['bitly.accesstoken' => null]);

    $user = User::factory()->create();

    $response = $this->actingAs($user, 'web')
        ->postJson('/api/assessments', ['title' => 'Short URL Test'])
        ->assertCreated()
        ->json();

    $password = $response['password'] ?? null;
    $short = $response['short_url'] ?? '';

    expect($password)->not->toBeNull();
    expect($short)->toContain('/client/' . $password);
});
