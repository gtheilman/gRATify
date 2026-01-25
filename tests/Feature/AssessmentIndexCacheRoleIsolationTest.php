<?php

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('does not reuse cached assessment index across roles', function () {
    Cache::flush();

    $admin = User::factory()->create(['role' => 'admin']);
    $editor = User::factory()->create(['role' => 'editor']);
    $owner = User::factory()->create();

    $adminAssessment = Assessment::factory()->for($admin, 'user')->create(['title' => 'Admin A']);
    $ownerAssessment = Assessment::factory()->for($owner, 'user')->create(['title' => 'Owner A']);

    $adminPayload = $this->actingAs($admin, 'web')
        ->getJson('/api/assessments')
        ->assertOk()
        ->json();

    expect(collect($adminPayload)->pluck('id')->all())
        ->toContain($adminAssessment->id, $ownerAssessment->id);

    $editorPayload = $this->actingAs($editor, 'web')
        ->getJson('/api/assessments')
        ->assertOk()
        ->json();

    expect(collect($editorPayload)->pluck('id')->all())
        ->not->toContain($adminAssessment->id);
});
