<?php

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns only own assessments for editor with cache enabled', function () {
    $editor = User::factory()->create(['role' => 'editor']);
    $other = User::factory()->create();

    $editorAssessment = Assessment::factory()->for($editor, 'user')->create();
    $otherAssessment = Assessment::factory()->for($other, 'user')->create();

    $payload = $this->actingAs($editor, 'web')
        ->getJson('/api/assessments')
        ->assertOk()
        ->json();

    expect(collect($payload)->pluck('id')->all())
        ->toContain($editorAssessment->id)
        ->not->toContain($otherAssessment->id);
});
