<?php

use App\Models\Assessment;
use App\Models\Presentation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('includes presentations_count when listing assessments by user', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $owner = User::factory()->create();
    config(['grat.admin_id' => $admin->id]);

    $assessment = Assessment::factory()->for($owner, 'user')->create();
    Presentation::factory()->for($assessment)->count(2)->create();

    $payload = $this->actingAs($admin, 'web')
        ->getJson("/api/list-assessments-by-user/{$owner->id}")
        ->assertOk()
        ->json();

    expect($payload)->toHaveCount(1);
    expect($payload[0]['id'])->toBe($assessment->id);
    expect($payload[0]['presentations_count'])->toBe(2);
});
