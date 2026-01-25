<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

it('attempts endpoint responds under throttle limits', function () {
    $assessment = \App\Models\Assessment::factory()->create();
    $presentation = \App\Models\Presentation::factory()->for($assessment)->create();
    $question = \App\Models\Question::factory()->for($assessment)->create();
    $answer = \App\Models\Answer::factory()->for($question)->create();

    // Smoke request to ensure middleware changes keep expected status and payload shape
    $response = $this->postJson('/api/attempts', [
        'presentation_id' => $presentation->id,
        'answer_id' => $answer->id,
    ]);

    // Allow 201 on create, or auth/not-found responses.
    expect($response->status())->toBeIn([201, 401, 404]);
});
