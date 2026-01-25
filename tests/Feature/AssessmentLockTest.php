<?php

use App\Models\Assessment;
use App\Models\Answer;
use App\Models\Attempt;
use App\Models\User;
use App\Models\Presentation;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Assessment lock tests
 *
 * This suite codifies the critical rule that once an assessment has responses
 * (presentations/attempts), its questions should no longer be editable.
 *
 * How to run:
 *   ./vendor/bin/pest --filter=AssessmentLockTest
 *
 * What it does:
 * - Builds an owner, assessment, question, answer, and a presentation+attempt to
 *   simulate “responses exist.”
 * - Calls the real question update API as the owner and expects a 403 with no
 *   database change.
 * - Verifies the happy path when there are no responses: the same update succeeds.
 *
 * Adjustments:
 * - If your API returns a different status code on lock (e.g., 422/409), update
 *   the assertions.
 * - Routes/payloads are based on the current controller validation (title/stem required).
 *
 * How to extend:
 * - If locking should apply to other endpoints (delete, reorder, etc.), mirror
 *   this pattern and add more scenarios.
 * - Uses RefreshDatabase so every test starts from a clean in-memory SQLite DB
 *   (configured in phpunit.xml), running your real migrations.
 */

uses(RefreshDatabase::class);

it('blocks edits when an assessment has responses', function () {
    // Arrange: owner + assessment + question/answer + response (presentation + attempt)
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $question = Question::factory()->for($assessment)->create(['sequence' => 1]);
    $answer = Answer::factory()->for($question)->create(['correct' => true, 'sequence' => 1]);

    $presentation = Presentation::factory()->for($assessment)->create();
    Attempt::factory()->for($presentation)->for($answer)->create();

    // Act + Assert: update should be rejected and DB unchanged because responses exist
    $this->actingAs($owner)
        ->putJson("/api/questions/{$question->id}", [
            'id' => $question->id,
            'stem' => 'New stem',
            'title' => $question->title,
        ])
        ->assertStatus(403);

    expect($question->fresh()->stem)->not->toBe('New stem');
});

it('allows edits when there are no responses', function () {
    // Arrange: owner + assessment + question, no responses created
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $question = Question::factory()->for($assessment)->create(['sequence' => 1]);

    // Act + Assert: update should succeed because there are no responses yet
    $this->actingAs($owner)
        ->putJson("/api/questions/{$question->id}", [
            'id' => $question->id,
            'stem' => 'Updated stem',
            'title' => $question->title,
        ])
        ->assertOk();

    expect($question->fresh()->stem)->toBe('Updated stem');
});

it('still blocks edits for admins when responses exist', function () {
    // Arrange: admin + assessment with responses
    $admin = User::factory()->create(['role' => 'admin']);
    $assessment = Assessment::factory()->for($admin, 'user')->create();
    $question = Question::factory()->for($assessment)->create(['sequence' => 1]);
    $answer = Answer::factory()->for($question)->create(['correct' => true, 'sequence' => 1]);

    $presentation = Presentation::factory()->for($assessment)->create();
    Attempt::factory()->for($presentation)->for($answer)->create();

    // Act + Assert: even admins should be blocked once responses exist
    $this->actingAs($admin)
        ->putJson("/api/questions/{$question->id}", [
            'id' => $question->id,
            'stem' => 'Admin edit attempt',
            'title' => $question->title,
        ])
        ->assertStatus(403);

    expect($question->fresh()->stem)->not->toBe('Admin edit attempt');
});
