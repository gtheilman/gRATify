<?php

use App\Models\Answer;
use App\Models\Assessment;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('serves the student SPA and completes the attempt flow', function () {
    if (! file_exists(public_path('build/manifest.json'))) {
        $this->markTestSkipped('Vite manifest missing; run npm run build to exercise SPA entry.');
    }

    $assessment = Assessment::factory()->create([
        'password' => 'demo123',
        'active' => true,
    ]);

    $question = Question::factory()->for($assessment)->create([
        'sequence' => 1,
        'stem' => 'What is 1 + 1?',
    ]);

    $wrong = Answer::factory()->for($question)->create([
        'answer_text' => '3',
        'sequence' => 1,
        'correct' => false,
    ]);

    $correct = Answer::factory()->for($question)->create([
        'answer_text' => '2',
        'sequence' => 2,
        'correct' => true,
    ]);

    $this->get('/client/demo123')
        ->assertOk()
        ->assertSee('gRAT - TBL Team Assessments');

    $presentationPayload = $this->getJson('/api/presentations/store/demo123/alice')
        ->assertCreated()
        ->assertJsonPath('assessment.questions.0.answers.1.answer_text', '2')
        ->json();

    $presentationId = data_get($presentationPayload, 'id');

    $this->postJson('/api/attempts', [
        'presentation_id' => $presentationId,
        'answer_id' => $correct->id,
    ])
        ->assertCreated()
        ->assertJson([
            'correct' => true,
            'alreadyAttempted' => false,
        ]);

    $this->assertDatabaseHas('attempts', [
        'presentation_id' => $presentationId,
        'answer_id' => $correct->id,
    ]);

    $this->postJson('/api/attempts', [
        'presentation_id' => $presentationId,
        'answer_id' => $correct->id,
    ])
        ->assertOk()
        ->assertJson([
            'correct' => false,
            'alreadyAttempted' => true,
        ]);

    $this->postJson('/api/attempts', [
        'presentation_id' => $presentationId,
        'answer_id' => $wrong->id,
    ])
        ->assertCreated()
        ->assertJson([
            'correct' => false,
            'alreadyAttempted' => false,
        ]);
});
