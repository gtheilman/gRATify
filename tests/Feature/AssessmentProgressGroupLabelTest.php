<?php

use App\Models\Assessment;
use App\Models\Presentation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns decrypted group labels for assessment progress', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();

    $encryptedId = encrypt('Group Alpha');
    Presentation::factory()->for($assessment)->create([
        'user_id' => $encryptedId,
    ]);

    $this->actingAs($owner, 'web')
        ->getJson("/api/assessment/attempts/{$assessment->id}")
        ->assertOk()
        ->assertJsonPath('presentations.0.group_label', 'Group Alpha');
});

it('falls back to raw group labels when not encrypted', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    Presentation::factory()->for($assessment)->create([
        'user_id' => 'Plain Group',
    ]);

    $this->actingAs($owner, 'web')
        ->getJson("/api/assessment/attempts/{$assessment->id}")
        ->assertOk()
        ->assertJsonPath('presentations.0.group_label', 'Plain Group');
});
