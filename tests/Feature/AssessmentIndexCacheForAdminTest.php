<?php

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns all assessments for admin with cache enabled', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $owner = User::factory()->create();

    $adminAssessment = Assessment::factory()->for($admin, 'user')->create();
    $ownerAssessment = Assessment::factory()->for($owner, 'user')->create();

    $payload = $this->actingAs($admin, 'web')
        ->getJson('/api/assessments')
        ->assertOk()
        ->json();

    expect(collect($payload)->pluck('id')->all())
        ->toContain($adminAssessment->id, $ownerAssessment->id);
});
