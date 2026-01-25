<?php

use App\Models\Answer;
use App\Models\Assessment;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns question store payload with expected fields', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();

    $this->actingAs($owner, 'web')
        ->postJson('/api/questions', [
            'title' => 'Contract Question',
            'stem' => 'Contract Stem',
            'assessment_id' => $assessment->id,
        ])
        ->assertCreated()
        ->assertJsonStructure([
            'id',
            'assessment_id',
            'title',
            'stem',
            'sequence',
        ])
        ->assertJsonPath('assessment_id', $assessment->id)
        ->assertJsonPath('title', 'Contract Question')
        ->assertJsonPath('stem', 'Contract Stem');
});

it('returns question update payload with expected fields', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $question = Question::factory()->for($assessment)->create([
        'title' => 'Old Title',
        'stem' => 'Old Stem',
        'sequence' => 1,
    ]);

    $this->actingAs($owner, 'web')
        ->putJson("/api/questions/{$question->id}", [
            'id' => $question->id,
            'title' => 'New Title',
            'stem' => 'New Stem',
            'sequence' => 1,
        ])
        ->assertOk()
        ->assertJsonPath('id', $question->id)
        ->assertJsonPath('title', 'New Title')
        ->assertJsonPath('stem', 'New Stem');
});

it('returns answer store payload with expected fields', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $question = Question::factory()->for($assessment)->create();

    $this->actingAs($owner, 'web')
        ->postJson('/api/answers', [
            'question_id' => $question->id,
            'answer_text' => 'Answer A',
            'correct' => true,
        ])
        ->assertCreated()
        ->assertJsonStructure([
            'id',
            'question_id',
            'answer_text',
            'correct',
            'sequence',
        ])
        ->assertJsonPath('question_id', $question->id)
        ->assertJsonPath('answer_text', 'Answer A')
        ->assertJsonPath('correct', true);
});

it('returns answer update payload with expected fields', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $question = Question::factory()->for($assessment)->create();
    $answer = Answer::factory()->for($question)->create([
        'answer_text' => 'Old Answer',
        'sequence' => 1,
        'correct' => false,
    ]);

    $this->actingAs($owner, 'web')
        ->putJson("/api/answers/{$answer->id}", [
            'id' => $answer->id,
            'answer_text' => 'New Answer',
            'correct' => true,
            'sequence' => 1,
        ])
        ->assertOk()
        ->assertJsonPath('id', $answer->id)
        ->assertJsonPath('answer_text', 'New Answer')
        ->assertJsonPath('correct', true);
});
