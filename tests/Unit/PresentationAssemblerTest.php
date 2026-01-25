<?php

use App\Models\Assessment;
use App\Models\Attempt;
use App\Models\Presentation;
use App\Models\Question;
use App\Models\Answer;
use App\Services\Presentations\PresentationAssembler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('creates a presentation when missing and attaches attempts', function () {
    $assessment = Assessment::factory()->create(['active' => true]);
    $question = Question::factory()->for($assessment)->create();
    $answer = Answer::factory()->for($question)->create();

    $service = app(PresentationAssembler::class);
    $result = $service->assemblePublic($assessment, 'student1');

    expect($result['created'])->toBeTrue();
    expect($result['presentation'])->toBeInstanceOf(Presentation::class);
    expect($result['presentation']->attempts)->toHaveCount(0);

    Attempt::factory()->create([
        'presentation_id' => $result['presentation']->id,
        'answer_id' => $answer->id,
    ]);

    $result = $service->assemblePublic($assessment, 'student1');
    expect($result['created'])->toBeFalse();
    expect($result['presentation']->attempts)->toHaveCount(1);
});
