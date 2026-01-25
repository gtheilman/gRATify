<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns 404 when updating a missing assessment', function () {
    $owner = User::factory()->create();
    $missingId = 99999;

    $this->actingAs($owner, 'web')
        ->putJson("/api/assessments/{$missingId}", [
            'title' => 'Updated title',
        ])
        ->assertStatus(404);
});
