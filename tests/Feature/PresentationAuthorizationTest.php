<?php

use App\Models\Answer;
use App\Models\Assessment;
use App\Models\Presentation;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires auth and admin for presentations completed list', function () {
    $this->getJson('/api/presentations/completed')->assertStatus(401);

    $regular = User::factory()->create();
    $this->actingAs($regular, 'web')
        ->getJson('/api/presentations/completed')
        ->assertStatus(403);

    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin, 'web')
        ->getJson('/api/presentations/completed')
        ->assertOk();
});

it('requires auth and ownership for score-by-assessment', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $question = Question::factory()->for($assessment)->create(['sequence' => 1]);
    Answer::factory()->for($question)->create(['correct' => true, 'sequence' => 1]);
    Presentation::factory()->for($assessment)->create();

    $this->getJson("/api/presentations/score-by-assessment-id/{$assessment->id}")
        ->assertStatus(401);

    $this->actingAs($other, 'web')
        ->getJson("/api/presentations/score-by-assessment-id/{$assessment->id}")
        ->assertStatus(403);

    $this->actingAs($owner, 'web')
        ->getJson("/api/presentations/score-by-assessment-id/{$assessment->id}")
        ->assertOk();
});

it('allows admins to view score-by-assessment', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $assessment = Assessment::factory()->for(User::factory()->create(), 'user')->create();
    $question = Question::factory()->for($assessment)->create(['sequence' => 1]);
    Answer::factory()->for($question)->create(['correct' => true, 'sequence' => 1]);
    Presentation::factory()->for($assessment)->create();

    $this->actingAs($admin, 'web')
        ->getJson("/api/presentations/score-by-assessment-id/{$assessment->id}")
        ->assertOk();
});
