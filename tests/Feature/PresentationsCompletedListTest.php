<?php

use App\Models\Assessment;
use App\Models\Presentation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns completed presentations excluding admin-owned assessments', function () {
    $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin User']);
    $owner = User::factory()->create(['name' => 'Owner User']);
    config(['grat.admin_id' => $admin->id]);

    $adminAssessment = Assessment::factory()->for($admin, 'user')->create([
        'title' => 'Admin Assessment',
    ]);
    $ownerAssessment = Assessment::factory()->for($owner, 'user')->create([
        'title' => 'Owner Assessment',
    ]);

    $adminPresentation = Presentation::factory()->for($adminAssessment)->create();
    $ownerPresentation = Presentation::factory()->for($ownerAssessment)->create();

    $response = $this->actingAs($admin, 'web')
        ->getJson('/api/presentations/completed')
        ->assertOk()
        ->json();

    expect($response)->toHaveCount(1);
    expect(data_get($response, '0.id'))->toBe($ownerPresentation->id);
    expect(data_get($response, '0.title'))->toBe('Owner Assessment');
    expect(data_get($response, '0.name'))->toBe('Owner User');
    expect(collect($response)->pluck('id')->all())->not->toContain($adminPresentation->id);
});
