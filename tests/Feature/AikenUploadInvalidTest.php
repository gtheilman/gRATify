<?php

use App\Models\Assessment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

it('returns 422 with errors for invalid aiken uploads', function () {
    $assessment = Assessment::factory()->create();
    $this->actingAs($assessment->user, 'web');

    $content = <<<TXT
This is not a valid aiken file
No answers follow
TXT;

    $file = UploadedFile::fake()->createWithContent('questions.txt', $content);

    $this->postJson('/api/questions/upload', [
        'assessment' => $file,
        'assessment_id' => $assessment->id,
    ])
        ->assertStatus(422)
        ->assertJsonStructure(['status', 'errors']);
});
