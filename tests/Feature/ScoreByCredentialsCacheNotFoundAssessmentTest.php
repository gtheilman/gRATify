<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns 404 for score-by-credentials when assessment is missing', function () {
    $this->getJson('/api/presentations/score-by-credentials/missing-password/student1')
        ->assertStatus(404);
});
