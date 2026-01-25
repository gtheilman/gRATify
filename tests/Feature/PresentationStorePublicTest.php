<?php

use App\Models\Answer;
use App\Models\Assessment;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns presentation payload for public store route', function () {
    $assessment = Assessment::factory()->create([
        'password' => 'e85ac6eb',
        'title' => '091820_LEND Practice Test_final',
        'short_url' => 'https://bitly.com/2GKZzZz',
        'active' => true,
    ]);

    $questions = Question::factory()
        ->count(2)
        ->for($assessment)
        ->sequence(
            ['stem' => 'Question one?', 'sequence' => 1],
            ['stem' => 'Question two?', 'sequence' => 2],
        )
        ->create();

    foreach ($questions as $question) {
        Answer::factory()
            ->count(2)
            ->for($question)
            ->sequence(
                ['answer_text' => 'A1', 'sequence' => 1],
                ['answer_text' => 'A2', 'sequence' => 2],
            )
            ->create();
    }

    $response = $this->getJson('/api/presentations/store/e85ac6eb/gary')
        ->assertCreated()
        ->assertJsonPath('user_id', 'gary')
        ->assertJsonPath('assessment_id', $assessment->id)
        ->assertJsonPath('assessment.id', $assessment->id)
        ->assertJsonPath('assessment.short_url', 'https://bitly.com/2GKZzZz')
        ->assertJsonPath('attempts', [])
        ->json();

    // ensure questions and answers are returned and sorted
    expect(data_get($response, 'assessment.questions'))->toHaveCount(2);
    expect(data_get($response, 'assessment.questions.0.answers'))->toHaveCount(2);
    // answer payloads should not expose correctness flags to participants
    expect(data_get($response, 'assessment.questions.0.answers.0'))->not->toHaveKey('correct');
});
