<?php

use App\Models\Assessment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns 404 for score-by-credentials when presentation is missing', function () {
    $assessment = Assessment::factory()->create(['password' => 'missingpass', 'active' => true]);

    $this->getJson('/api/presentations/score-by-credentials/missingpass/student1')
        ->assertStatus(404);
});
