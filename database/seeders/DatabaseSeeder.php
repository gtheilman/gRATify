<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Assessment;
use App\Models\Question;
use App\Models\Answer;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Pre-hashed seed passwords so we can later detect if they were changed.
        $adminSeedHash = '$2y$12$Lar5T5y8docuOFsdx98FRevUlRMZRP/40zpowaLJHz2ZtN9b/pww2'; // "admin"
        $editorSeedHash = '$2y$12$UpLAGr/bxe5O2JRun4Ih0enYSKYpVFmwOO1WTdYgqG8UNNKYEPP2S'; // "demo"

        // Default admin account for first login; credentials can be changed later.
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'username' => 'admin',
                'role' => 'admin',
                'password' => $adminSeedHash,
            ],
        );

        // Single demo editor account (password: "demo"). Intended for testing; remove before production use.
        User::updateOrCreate(
            ['email' => 'editor@example.com'],
            [
                'name' => 'Casey Demo',
                'username' => 'editor',
                'role' => 'editor',
                'password' => $editorSeedHash,
            ],
        );

        // Demo assessment with 10 multiple-choice questions (non-controversial, knowledge-based).
        $admin = User::firstWhere('email', 'admin@example.com');

        if ($admin) {
            $assessment = Assessment::updateOrCreate(
                ['password' => 'demo-assessment'],
                [
                    'title' => 'Sample Knowledge Check',
                    'user_id' => $admin->id,
                    'course' => 'Demo Course',
                    'memo' => 'Demo data seeded for first-time setup. Remove or replace before production.',
                    'active' => true,
                ],
            );

            $questions = [
                [
                    'stem' => 'What is the capital of France?',
                    'answers' => [
                        ['Paris', true],
                        ['Lyon', false],
                        ['Marseille', false],
                        ['Nice', false],
                    ],
                ],
                [
                    'stem' => 'Water boils at what temperature at sea level?',
                    'answers' => [
                        ['90°C', false],
                        ['95°C', false],
                        ['100°C', true],
                        ['105°C', false],
                    ],
                ],
                [
                    'stem' => 'Which planet is known as the Red Planet?',
                    'answers' => [
                        ['Mars', true],
                        ['Venus', false],
                        ['Jupiter', false],
                        ['Mercury', false],
                    ],
                ],
                [
                    'stem' => 'What gas do plants primarily use for photosynthesis?',
                    'answers' => [
                        ['Carbon dioxide', true],
                        ['Oxygen', false],
                        ['Nitrogen', false],
                        ['Hydrogen', false],
                    ],
                ],
                [
                    'stem' => 'Which organ pumps blood through the human body?',
                    'answers' => [
                        ['Heart', true],
                        ['Liver', false],
                        ['Lung', false],
                        ['Kidney', false],
                    ],
                ],
                [
                    'stem' => 'How many continents are there on Earth?',
                    'answers' => [
                        ['Five', false],
                        ['Six', false],
                        ['Seven', true],
                        ['Eight', false],
                    ],
                ],
                [
                    'stem' => 'What is the chemical symbol for table salt?',
                    'answers' => [
                        ['NaCl', true],
                        ['KCl', false],
                        ['CaCl', false],
                        ['NaOH', false],
                    ],
                ],
                [
                    'stem' => 'Which instrument measures atmospheric pressure?',
                    'answers' => [
                        ['Barometer', true],
                        ['Thermometer', false],
                        ['Anemometer', false],
                        ['Hygrometer', false],
                    ],
                ],
                [
                    'stem' => 'Who wrote “Romeo and Juliet”?',
                    'answers' => [
                        ['William Shakespeare', true],
                        ['Charles Dickens', false],
                        ['Mark Twain', false],
                        ['Jane Austen', false],
                    ],
                ],
                [
                    'stem' => 'What is the largest ocean on Earth?',
                    'answers' => [
                        ['Pacific Ocean', true],
                        ['Atlantic Ocean', false],
                        ['Indian Ocean', false],
                        ['Arctic Ocean', false],
                    ],
                ],
            ];

            foreach ($questions as $index => $q) {
                $question = Question::updateOrCreate(
                    [
                        'assessment_id' => $assessment->id,
                        'sequence' => $index + 1,
                    ],
                    [
                        'title' => "Question " . ($index + 1),
                        'stem' => $q['stem'],
                        'points_possible' => null,
                    ],
                );

                foreach ($q['answers'] as $answerIndex => [$text, $isCorrect]) {
                    Answer::updateOrCreate(
                        [
                            'question_id' => $question->id,
                            'sequence' => $answerIndex + 1,
                        ],
                        [
                            'answer_text' => $text,
                            'correct' => $isCorrect,
                        ],
                    );
                }
            }

            // Advanced formatting demo assessment.
            $formatAssessment = Assessment::updateOrCreate(
                ['password' => 'demo-formatting'],
                [
                    'title' => 'Advanced Formatting Showcase',
                    'user_id' => $admin->id,
                    'course' => 'Demo Course',
                    'memo' => 'Demonstrates markdown, links, images, LaTeX, and AsciiMath.',
                    'active' => true,
                ],
            );

            $formatQuestions = [
                [
                    'stem' => 'Inline markdown works: **bold**, _italic_, and `code` all render.',
                    'answers' => [
                        ['Yes, that renders as expected.', true],
                        ['No, markdown is stripped.', false],
                        ['Only bold works.', false],
                        ['Only code works.', false],
                    ],
                ],
                [
                    'stem' => 'Hyperlinks: visit [CDC Influenza](https://www.cdc.gov/flu/) to verify.',
                    'answers' => [
                        ['The link renders and is clickable.', true],
                        ['Links are removed automatically.', false],
                        ['Links show as plain text only.', false],
                        ['Links break the question layout.', false],
                    ],
                ],
                [
                    'stem' => <<<'TEXT'
Inline LaTeX: $E = mc^2$. Block math:

$$a^2 + b^2 = c^2$$

Both inline and block math should render with KaTeX.
TEXT,
                    'answers' => [
                        ['Both inline and block math render with KaTeX.', true],
                        ['Inline renders, block does not.', false],
                        ['Block renders, inline does not.', false],
                        ['Neither renders.', false],
                    ],
                ],
                [
                    'stem' => 'AsciiMath example: `@int_0^1 x^2 dx@` should show an integral.',
                    'answers' => [
                        ['AsciiMath renders to LaTeX delimiters.', true],
                        ['AsciiMath is displayed as raw text.', false],
                        ['AsciiMath causes an error.', false],
                        ['AsciiMath removes the question.', false],
                    ],
                ],
                [
                    'stem' => 'Embedded image: <img src="https://upload.wikimedia.org/wikipedia/commons/3/3c/Shaki_waterfall.jpg" alt="Waterfall" width="100" height="100" />',
                    'answers' => [
                        ['The image displays inline above.', true],
                        ['Images are blocked entirely.', false],
                        ['Images require upload to work.', false],
                        ['Images replace all text.', false],
                    ],
                ],
                [
                    'stem' => 'Emoji example: :sunglasses: should show a sunglasses emoji.',
                    'answers' => [
                        ['The emoji displays as expected.', true],
                        ['Only a placeholder box appears.', false],
                        ['Emojis remove surrounding text.', false],
                        ['Emojis render as raw text.', false],
                    ],
                ],
            ];

            foreach ($formatQuestions as $index => $q) {
                $question = Question::updateOrCreate(
                    [
                        'assessment_id' => $formatAssessment->id,
                        'sequence' => $index + 1,
                    ],
                    [
                        'title' => "Formatting Question " . ($index + 1),
                        'stem' => $q['stem'],
                        'points_possible' => null,
                    ],
                );

                foreach ($q['answers'] as $answerIndex => [$text, $isCorrect]) {
                    Answer::updateOrCreate(
                        [
                            'question_id' => $question->id,
                            'sequence' => $answerIndex + 1,
                        ],
                        [
                            'answer_text' => $text,
                            'correct' => $isCorrect,
                        ],
                    );
                }
            }
        }
    }
}
