<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Ensures the Assessments menu route is reachable.
 */
uses(RefreshDatabase::class);

it('serves /assessments to unauthenticated users (current behavior)', function () {
    $this->get('/assessments')->assertOk();
});

it('renders /assessments for authenticated users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/assessments')
        ->assertOk();
});
