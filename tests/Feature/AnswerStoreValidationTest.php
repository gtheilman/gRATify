<?php

use App\Models\Assessment;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('rejects answer creation with invalid question_id', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    Question::factory()->for($assessment)->create();

    $this->actingAs($owner, 'web')
        ->postJson('/api/answers', [
            'answer_text' => 'Answer',
            'question_id' => 9999,
        ])
        ->assertStatus(422);
});
