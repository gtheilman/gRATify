<?php

use App\Models\Assessment;
use App\Models\Presentation;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns error envelope for unauthenticated requests', function () {
    $this->getJson('/api/assessments')
        ->assertStatus(401)
        ->assertJsonPath('error.code', 'unauthenticated')
        ->assertJsonPath('error.message', 'Unauthenticated');
});

it('returns error envelope for forbidden requests', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();

    $this->actingAs($other, 'web')
        ->postJson('/api/questions', [
            'title' => 'Should not create',
            'stem' => 'Should not create',
            'assessment_id' => $assessment->id,
        ])
        ->assertStatus(403)
        ->assertJsonPath('error.code', 'forbidden')
        ->assertJsonPath('error.message', 'Forbidden');
});

it('returns error envelope for not found resources', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'web')
        ->getJson('/api/assessments/999999/edit')
        ->assertStatus(404)
        ->assertJsonPath('error.code', 'not_found')
        ->assertJsonPath('error.message', 'Not Found');
});

it('returns error envelope for locked requests', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $question = Question::factory()->for($assessment)->create(['sequence' => 1]);
    Presentation::factory()->for($assessment)->create();

    $this->actingAs($owner, 'web')
        ->patchJson("/api/questions/{$question->id}", [
            'id' => $question->id,
            'title' => 'Updated',
            'stem' => 'Updated',
            'sequence' => 1,
        ])
        ->assertStatus(403)
        ->assertJsonPath('error.code', 'locked')
        ->assertJsonPath('error.message', 'Locked');
});
