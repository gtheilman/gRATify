<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires auth for assessment index', function () {
    $this->getJson('/api/assessments')->assertStatus(401);

    $user = User::factory()->create();
    $this->actingAs($user, 'web')
        ->getJson('/api/assessments')
        ->assertOk();
});
