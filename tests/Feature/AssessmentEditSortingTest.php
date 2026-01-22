<?php

use App\Models\Assessment;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns edit payload with questions sorted by sequence', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();

    Question::factory()->for($assessment)->create(['sequence' => 2, 'stem' => 'Second']);
    Question::factory()->for($assessment)->create(['sequence' => 1, 'stem' => 'First']);

    $response = $this->actingAs($owner, 'web')
        ->getJson("/api/assessments/{$assessment->id}/edit")
        ->assertOk()
        ->json();

    $questions = data_get($response, 'questions');
    expect($questions)->toHaveCount(2);
    expect(data_get($questions, '0.sequence'))->toBe(1);
    expect(data_get($questions, '0.stem'))->toBe('First');
});
