<?php

use App\Models\Answer;
use App\Models\Assessment;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Exceptions\CustomException;

/**
 * Simulates browser-level interactions on /assessments/{id} via the API endpoints.
 */
uses(RefreshDatabase::class);

it('allows editing assessment details', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create(['title' => 'Original', 'course' => 'Orig']);

    $this->actingAs($owner, 'web')
        ->putJson("/api/assessments/{$assessment->id}", [
            'title' => 'Updated Title',
            'course' => 'Updated Course',
            'active' => false,
        ])
        ->assertOk();

    $fresh = $assessment->fresh();
    expect($fresh->title)->toBe('Updated Title');
    expect($fresh->course)->toBe('Updated Course');
    expect((bool) $fresh->active)->toBeFalse();
});

it('allows adding and deleting questions', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();

    // Add question
    $questionId = $this->actingAs($owner, 'web')
        ->postJson('/api/questions', [
            'title' => 'Q1',
            'stem' => 'Stem 1',
            'assessment_id' => $assessment->id,
        ])
        ->assertCreated()
        ->json('id');

    expect($questionId)->not->toBeNull();
    expect(Question::where('assessment_id', $assessment->id)->count())->toBe(1);

    // Delete question
    $this->actingAs($owner, 'web')
        ->deleteJson("/api/questions/{$questionId}")
        ->assertNoContent();

    expect(Question::where('assessment_id', $assessment->id)->count())->toBe(0);
});

it('allows reordering questions via promote', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $q1 = Question::factory()->for($assessment)->create(['sequence' => 1, 'title' => 'Q1']);
    $q2 = Question::factory()->for($assessment)->create(['sequence' => 2, 'title' => 'Q2']);

    // Promote Q2 to position 1
    $this->actingAs($owner, 'web')
        ->postJson('/api/questions/promote', [
            'question_id' => $q2->id,
            'assessment_id' => $assessment->id,
        ])
        ->assertStatus(200)
        ->assertJsonFragment(['status' => 'Renumbered']);

    $sequences = Question::whereIn('id', [$q1->id, $q2->id])
        ->orderBy('sequence')
        ->pluck('title')
        ->all();

    expect($sequences)->toBe(['Q2', 'Q1']);
});

it('allows reordering questions via demote', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $q1 = Question::factory()->for($assessment)->create(['sequence' => 1, 'title' => 'Q1']);
    $q2 = Question::factory()->for($assessment)->create(['sequence' => 2, 'title' => 'Q2']);

    // Demote Q1 to position 2
    $this->actingAs($owner, 'web')
        ->postJson('/api/questions/demote', [
            'question_id' => $q1->id,
            'assessment_id' => $assessment->id,
        ])
        ->assertStatus(200)
        ->assertJsonFragment(['status' => 'Renumbered']);

    $sequences = Question::whereIn('id', [$q1->id, $q2->id])
        ->orderBy('sequence')
        ->pluck('title')
        ->all();

    expect($sequences)->toBe(['Q2', 'Q1']);
});

it('allows adding and deleting answers', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $question = Question::factory()->for($assessment)->create(['sequence' => 1]);

    // Add answer
    $answerId = $this->actingAs($owner, 'web')
        ->postJson('/api/answers', [
            'answer_text' => 'A1',
            'question_id' => $question->id,
            'sequence' => 1,
        ])
        ->assertCreated()
        ->json('id');

    expect($answerId)->not->toBeNull();
    expect(Answer::where('question_id', $question->id)->count())->toBe(1);

    // Delete answer
    $this->actingAs($owner, 'web')
        ->deleteJson("/api/answers/{$answerId}")
        ->assertNoContent();

    expect(Answer::where('question_id', $question->id)->count())->toBe(0);
});

it('allows reordering answers via promote endpoint', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $question = Question::factory()->for($assessment)->create(['sequence' => 1]);

    $a1 = Answer::factory()->for($question)->create(['sequence' => 1, 'answer_text' => 'A1']);
    $a2 = Answer::factory()->for($question)->create(['sequence' => 2, 'answer_text' => 'A2']);

    $this->actingAs($owner, 'web')
        ->postJson('/api/answers/promote', [
            'answer_id' => $a2->id,
            'question_id' => $question->id,
        ])
        ->assertStatus(200)
        ->assertJsonFragment(['status' => 'Renumbered']);

    $order = Answer::whereIn('id', [$a1->id, $a2->id])
        ->orderBy('sequence')
        ->pluck('answer_text')
        ->all();

    expect($order)->toBe(['A2', 'A1']);
});

it('allows reordering answers via demote endpoint', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $question = Question::factory()->for($assessment)->create(['sequence' => 1]);

    $a1 = Answer::factory()->for($question)->create(['sequence' => 1, 'answer_text' => 'A1']);
    $a2 = Answer::factory()->for($question)->create(['sequence' => 2, 'answer_text' => 'A2']);

    $this->actingAs($owner, 'web')
        ->postJson('/api/answers/demote', [
            'answer_id' => $a1->id,
            'question_id' => $question->id,
        ])
        ->assertStatus(200)
        ->assertJsonFragment(['status' => 'Renumbered']);

    $order = Answer::whereIn('id', [$a1->id, $a2->id])
        ->orderBy('sequence')
        ->pluck('answer_text')
        ->all();

    expect($order)->toBe(['A2', 'A1']);
});

it('returns custom error view when CustomException is thrown', function () {
    $handler = app(\App\Exceptions\Handler::class);
    $response = $handler->render(request(), new CustomException('Boom'));
    expect($response->getStatusCode())->toBe(500);
    expect($response->getContent())->toContain('Something went wrong.');
});
