<?php

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns 404 for score-by-assessment when assessment is missing', function () {
    $owner = User::factory()->create();

    $this->actingAs($owner, 'web')
        ->getJson('/api/presentations/score-by-assessment-id/999999')
        ->assertStatus(404);
});
