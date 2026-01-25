<?php

use App\Models\Assessment;
use App\Models\Presentation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('does not serve cached completed list to non-admins', function () {
    Cache::flush();

    $admin = User::factory()->create(['role' => 'admin']);
    $editor = User::factory()->create(['role' => 'editor']);
    config(['grat.admin_id' => $admin->id]);

    $assessment = Assessment::factory()->for($editor, 'user')->create();
    Presentation::factory()->for($assessment)->create();

    $this->actingAs($admin, 'web')
        ->getJson('/api/presentations/completed')
        ->assertOk();

    $this->actingAs($editor, 'web')
        ->getJson('/api/presentations/completed')
        ->assertStatus(403);
});
