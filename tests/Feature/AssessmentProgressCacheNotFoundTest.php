<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns 404 for missing assessment progress even if cache is empty', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'web')
        ->getJson('/api/assessment/attempts/999999')
        ->assertStatus(404);
});
