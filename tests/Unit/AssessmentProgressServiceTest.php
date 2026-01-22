<?php

use App\Models\Assessment;
use App\Models\Presentation;
use App\Models\User;
use App\Services\Assessments\AssessmentProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('builds assessment progress with decrypted group labels', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $encryptedId = encrypt('Group Alpha');
    Presentation::factory()->for($assessment)->create([
        'user_id' => $encryptedId,
    ]);

    $service = app(AssessmentProgressService::class);
    $result = $service->build($assessment->id);

    expect($result->presentations)->toHaveCount(1)
        ->and($result->presentations->first()->group_label)->toBe('Group Alpha');
});
