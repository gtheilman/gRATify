<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires auth for shortlink providers endpoint', function () {
    $this->getJson('/api/shortlink-providers')->assertStatus(401);

    $user = User::factory()->create();
    $this->actingAs($user, 'web')
        ->getJson('/api/shortlink-providers')
        ->assertOk();
});
