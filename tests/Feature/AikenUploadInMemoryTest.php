<?php

use App\Models\Assessment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('processes aiken uploads fully in memory without touching storage', function () {
    Storage::fake('local');

    $assessment = Assessment::factory()->create();
    $this->actingAs($assessment->user, 'web');

    $content = <<<TXT
What is 2+2?
A) 3
B) 4
ANSWER: B
TXT;

    $file = UploadedFile::fake()->createWithContent('questions.txt', $content);

    $response = $this->postJson('/api/questions/upload', [
        'assessment' => $file,
        'assessment_id' => $assessment->id,
    ]);

    $response->assertOk();

    $this->assertDatabaseHas('questions', [
        'assessment_id' => $assessment->id,
        'title' => 'What is 2+2?',
    ]);

    // No files should be written to storage.
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
});

it('accepts multiline question stems with blank lines for math blocks', function () {
    $assessment = Assessment::factory()->create();
    $this->actingAs($assessment->user, 'web');

    $content = <<<TXT
Inline LaTeX: \$E = mc^2\$. Block math:

\$\$a^2 + b^2 = c^2\$\$

Both inline and block math should render with KaTeX.
A) Both inline and block math render with KaTeX.
B) Inline renders, block does not.
ANSWER: A
TXT;

    $file = UploadedFile::fake()->createWithContent('questions.txt', $content);

    $response = $this->postJson('/api/questions/upload', [
        'assessment' => $file,
        'assessment_id' => $assessment->id,
    ]);

    $response->assertOk();

    $question = \App\Models\Question::where('assessment_id', $assessment->id)->first();
    expect($question)->not->toBeNull();
    expect($question->stem)->toContain('Inline LaTeX: $E = mc^2$');
    expect($question->stem)->toContain("\n\n\$\$a^2 + b^2 = c^2\$\$\n\n");
    expect($question->stem)->toContain('Both inline and block math should render with KaTeX.');
});
