<?php

use App\Models\Assessment;
use App\Models\Presentation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns assessment index payload with presentation counts for owner', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create([
        'title' => 'Index Contract',
    ]);
    Presentation::factory()->for($assessment)->count(2)->create();

    $this->actingAs($owner, 'web')
        ->getJson('/api/assessments')
        ->assertOk()
        ->assertJsonStructure([
            '*' => [
                'id',
                'user_id',
                'owner_username',
                'title',
                'short_url',
                'presentations_count',
                'created_at',
            ],
        ])
        ->assertJsonPath('0.id', $assessment->id)
        ->assertJsonPath('0.presentations_count', 2);
});
