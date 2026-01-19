<?php

namespace Database\Seeders;

use App\Models\Answer;
use App\Models\Assessment;
use App\Models\Attempt;
use App\Models\Presentation;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $topics = [
            'Geography',
            'History',
            'Science',
            'Literature',
            'Sports',
            'Music',
            'Art',
            'Technology',
            'Politics',
            'Pop Culture',
        ];

        $questionBank = $this->questionBank();
        $penalties = ['percent', 'logarithmic', null];

        for ($editorIndex = 1; $editorIndex <= 10; $editorIndex++) {
            $name = $this->ordinalEditorName($editorIndex);

            $user = User::create([
                'username' => "editor{$editorIndex}",
                'name' => $name,
                'email' => "editor{$editorIndex}@example.com",
                'password' => Hash::make('password'),
                'role' => 'editor',
                'company' => 'Demo University',
            ]);

            foreach ($topics as $topicIndex => $topic) {
                $assessment = Assessment::create([
                    'title' => "{$topic} gRAT {$editorIndex}",
                    'user_id' => $user->id,
                    'time_limit' => 45,
                    'penalty_method' => $penalties[($topicIndex + $editorIndex) % count($penalties)],
                    'course' => "{$topic} 101",
                    'short_url' => null,
                    'scheduled_at' => now()->addDays($topicIndex + $editorIndex),
                    'memo' => "{$topic} gRAT created by {$name} for demo use.",
                    'password' => $this->uniqueAssessmentPassword($editorIndex, $topicIndex + 1),
                    'active' => true,
                ]);

                $questions = collect();

                $topicQuestions = $questionBank[$topic] ?? [];
                foreach ($topicQuestions as $questionIndex => $questionData) {
                    $sequence = $questionIndex + 1;
                    $question = Question::create([
                        'assessment_id' => $assessment->id,
                        'title' => "Question {$sequence}",
                        'stem' => $questionData['question'],
                        'points_possible' => 1.0,
                        'sequence' => $sequence,
                    ]);

                    foreach ($questionData['answers'] as $answerIndex => $answer) {
                        Answer::create([
                            'question_id' => $question->id,
                            'answer_text' => $answer['text'],
                            'feedback' => $answer['correct'] ? 'Correct.' : 'Not correct. Try again.',
                            'correct' => $answer['correct'],
                            'sequence' => $answerIndex + 1,
                        ]);
                    }

                    $questions->push($question->load('answers'));
                }

                if ($topicIndex < 5) {
                    $this->seedPresentations($assessment, $questions);
                }
            }
        }
    }

    private function seedPresentations(Assessment $assessment, $questions): void
    {
        for ($groupIndex = 1; $groupIndex <= 10; $groupIndex++) {
            $presentation = Presentation::create([
                'assessment_id' => $assessment->id,
                'user_id' => "Group {$groupIndex}",
            ]);

            $progressTarget = match (true) {
                $groupIndex <= 3 => 10,
                $groupIndex <= 7 => 5,
                default => 2,
            };

            foreach ($questions->take($progressTarget) as $questionIndex => $question) {
                $answers = $question->answers->values();
                $correct = $answers->firstWhere('correct', true);
                $incorrect = $answers->where('correct', false)->values();
                $attempts = (($groupIndex + $questionIndex) % 3) + 1;

                for ($attemptIndex = 0; $attemptIndex < $attempts - 1; $attemptIndex++) {
                    $choice = $incorrect[$attemptIndex % $incorrect->count()];
                    Attempt::create([
                        'presentation_id' => $presentation->id,
                        'answer_id' => $choice->id,
                        'points' => 0,
                    ]);
                }

                Attempt::create([
                    'presentation_id' => $presentation->id,
                    'answer_id' => $correct->id,
                    'points' => 1,
                ]);
            }
        }
    }

    private function ordinalEditorName(int $index): string
    {
        $ordinals = [
            1 => 'One',
            2 => 'Two',
            3 => 'Three',
            4 => 'Four',
            5 => 'Five',
            6 => 'Six',
            7 => 'Seven',
            8 => 'Eight',
            9 => 'Nine',
            10 => 'Ten',
        ];

        return 'Editor ' . ($ordinals[$index] ?? (string) $index);
    }

    private function uniqueAssessmentPassword(int $editorIndex, int $topicIndex): string
    {
        $seed = sprintf('ed%02dgr%02d', $editorIndex, $topicIndex);
        return $seed . Str::lower(Str::random(4));
    }

    private function questionBank(): array
    {
        return [
            'Geography' => [
                [
                    'question' => 'What is the longest river in the world?',
                    'answers' => [
                        ['text' => 'Nile', 'correct' => true],
                        ['text' => 'Amazon', 'correct' => false],
                        ['text' => 'Yangtze', 'correct' => false],
                        ['text' => 'Mississippi', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which country has the largest land area?',
                    'answers' => [
                        ['text' => 'Russia', 'correct' => true],
                        ['text' => 'Canada', 'correct' => false],
                        ['text' => 'China', 'correct' => false],
                        ['text' => 'United States', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'What is the capital of Australia?',
                    'answers' => [
                        ['text' => 'Canberra', 'correct' => true],
                        ['text' => 'Sydney', 'correct' => false],
                        ['text' => 'Melbourne', 'correct' => false],
                        ['text' => 'Brisbane', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which desert is the largest in the world?',
                    'answers' => [
                        ['text' => 'Antarctic Desert', 'correct' => true],
                        ['text' => 'Sahara', 'correct' => false],
                        ['text' => 'Gobi', 'correct' => false],
                        ['text' => 'Arabian', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Mount Kilimanjaro is located in which country?',
                    'answers' => [
                        ['text' => 'Tanzania', 'correct' => true],
                        ['text' => 'Kenya', 'correct' => false],
                        ['text' => 'Uganda', 'correct' => false],
                        ['text' => 'Ethiopia', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which continent has the most countries?',
                    'answers' => [
                        ['text' => 'Africa', 'correct' => true],
                        ['text' => 'Europe', 'correct' => false],
                        ['text' => 'Asia', 'correct' => false],
                        ['text' => 'South America', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'What is the smallest country in the world by land area?',
                    'answers' => [
                        ['text' => 'Vatican City', 'correct' => true],
                        ['text' => 'Monaco', 'correct' => false],
                        ['text' => 'San Marino', 'correct' => false],
                        ['text' => 'Liechtenstein', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which U.S. state is known as the "Sunshine State"?',
                    'answers' => [
                        ['text' => 'Florida', 'correct' => true],
                        ['text' => 'California', 'correct' => false],
                        ['text' => 'Arizona', 'correct' => false],
                        ['text' => 'Texas', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which ocean is the deepest?',
                    'answers' => [
                        ['text' => 'Pacific Ocean', 'correct' => true],
                        ['text' => 'Atlantic Ocean', 'correct' => false],
                        ['text' => 'Indian Ocean', 'correct' => false],
                        ['text' => 'Southern Ocean', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'The city of Istanbul straddles which two continents?',
                    'answers' => [
                        ['text' => 'Europe and Asia', 'correct' => true],
                        ['text' => 'Africa and Europe', 'correct' => false],
                        ['text' => 'Asia and Africa', 'correct' => false],
                        ['text' => 'Europe and South America', 'correct' => false],
                    ],
                ],
            ],
            'History' => [
                [
                    'question' => 'What year was George Washington born?',
                    'answers' => [
                        ['text' => '1732', 'correct' => true],
                        ['text' => '1715', 'correct' => false],
                        ['text' => '1748', 'correct' => false],
                        ['text' => '1756', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which war ended with the Treaty of Versailles?',
                    'answers' => [
                        ['text' => 'World War I', 'correct' => true],
                        ['text' => 'World War II', 'correct' => false],
                        ['text' => 'The Crimean War', 'correct' => false],
                        ['text' => 'The Franco-Prussian War', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Who was the first emperor of Rome?',
                    'answers' => [
                        ['text' => 'Augustus', 'correct' => true],
                        ['text' => 'Julius Caesar', 'correct' => false],
                        ['text' => 'Nero', 'correct' => false],
                        ['text' => 'Constantine', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'The Magna Carta was signed in which year?',
                    'answers' => [
                        ['text' => '1215', 'correct' => true],
                        ['text' => '1066', 'correct' => false],
                        ['text' => '1492', 'correct' => false],
                        ['text' => '1314', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which empire built Machu Picchu?',
                    'answers' => [
                        ['text' => 'Inca', 'correct' => true],
                        ['text' => 'Aztec', 'correct' => false],
                        ['text' => 'Maya', 'correct' => false],
                        ['text' => 'Olmec', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Who was the British Prime Minister during most of World War II?',
                    'answers' => [
                        ['text' => 'Winston Churchill', 'correct' => true],
                        ['text' => 'Neville Chamberlain', 'correct' => false],
                        ['text' => 'Clement Attlee', 'correct' => false],
                        ['text' => 'Anthony Eden', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'The Berlin Wall fell in what year?',
                    'answers' => [
                        ['text' => '1989', 'correct' => true],
                        ['text' => '1979', 'correct' => false],
                        ['text' => '1991', 'correct' => false],
                        ['text' => '1968', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which civilization is credited with inventing paper?',
                    'answers' => [
                        ['text' => 'China', 'correct' => true],
                        ['text' => 'Egypt', 'correct' => false],
                        ['text' => 'Greece', 'correct' => false],
                        ['text' => 'Mesopotamia', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Who was the first woman to fly solo across the Atlantic Ocean?',
                    'answers' => [
                        ['text' => 'Amelia Earhart', 'correct' => true],
                        ['text' => 'Bessie Coleman', 'correct' => false],
                        ['text' => 'Harriet Quimby', 'correct' => false],
                        ['text' => 'Jacqueline Cochran', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'The ancient city of Petra is located in which modern-day country?',
                    'answers' => [
                        ['text' => 'Jordan', 'correct' => true],
                        ['text' => 'Syria', 'correct' => false],
                        ['text' => 'Israel', 'correct' => false],
                        ['text' => 'Lebanon', 'correct' => false],
                    ],
                ],
            ],
            'Science' => [
                [
                    'question' => 'What is the chemical symbol for gold?',
                    'answers' => [
                        ['text' => 'Au', 'correct' => true],
                        ['text' => 'Ag', 'correct' => false],
                        ['text' => 'Gd', 'correct' => false],
                        ['text' => 'Go', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which planet is known as the Red Planet?',
                    'answers' => [
                        ['text' => 'Mars', 'correct' => true],
                        ['text' => 'Venus', 'correct' => false],
                        ['text' => 'Jupiter', 'correct' => false],
                        ['text' => 'Mercury', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'What is the largest organ in the human body?',
                    'answers' => [
                        ['text' => 'Skin', 'correct' => true],
                        ['text' => 'Liver', 'correct' => false],
                        ['text' => 'Heart', 'correct' => false],
                        ['text' => 'Lungs', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'What gas do plants absorb from the atmosphere?',
                    'answers' => [
                        ['text' => 'Carbon dioxide', 'correct' => true],
                        ['text' => 'Oxygen', 'correct' => false],
                        ['text' => 'Nitrogen', 'correct' => false],
                        ['text' => 'Hydrogen', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'How many bones are in the adult human body?',
                    'answers' => [
                        ['text' => '206', 'correct' => true],
                        ['text' => '201', 'correct' => false],
                        ['text' => '212', 'correct' => false],
                        ['text' => '198', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'What is the process by which plants make food?',
                    'answers' => [
                        ['text' => 'Photosynthesis', 'correct' => true],
                        ['text' => 'Respiration', 'correct' => false],
                        ['text' => 'Fermentation', 'correct' => false],
                        ['text' => 'Digestion', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'What is the speed of light in vacuum (approx.)?',
                    'answers' => [
                        ['text' => '300,000 km/s', 'correct' => true],
                        ['text' => '150,000 km/s', 'correct' => false],
                        ['text' => '30,000 km/s', 'correct' => false],
                        ['text' => '3,000 km/s', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which blood type is known as the universal donor?',
                    'answers' => [
                        ['text' => 'O negative', 'correct' => true],
                        ['text' => 'AB positive', 'correct' => false],
                        ['text' => 'A positive', 'correct' => false],
                        ['text' => 'B negative', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'What is the powerhouse of the cell?',
                    'answers' => [
                        ['text' => 'Mitochondria', 'correct' => true],
                        ['text' => 'Nucleus', 'correct' => false],
                        ['text' => 'Ribosome', 'correct' => false],
                        ['text' => 'Golgi apparatus', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which element has the atomic number 1?',
                    'answers' => [
                        ['text' => 'Hydrogen', 'correct' => true],
                        ['text' => 'Helium', 'correct' => false],
                        ['text' => 'Oxygen', 'correct' => false],
                        ['text' => 'Carbon', 'correct' => false],
                    ],
                ],
            ],
            'Literature' => [
                [
                    'question' => 'Who wrote "Pride and Prejudice"?',
                    'answers' => [
                        ['text' => 'Jane Austen', 'correct' => true],
                        ['text' => 'Charlotte Bronte', 'correct' => false],
                        ['text' => 'Emily Bronte', 'correct' => false],
                        ['text' => 'Mary Shelley', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which novel begins with "Call me Ishmael"?',
                    'answers' => [
                        ['text' => 'Moby-Dick', 'correct' => true],
                        ['text' => 'The Old Man and the Sea', 'correct' => false],
                        ['text' => 'Treasure Island', 'correct' => false],
                        ['text' => 'The Odyssey', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Who wrote the play "Romeo and Juliet"?',
                    'answers' => [
                        ['text' => 'William Shakespeare', 'correct' => true],
                        ['text' => 'Christopher Marlowe', 'correct' => false],
                        ['text' => 'Ben Jonson', 'correct' => false],
                        ['text' => 'John Milton', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which author wrote "1984"?',
                    'answers' => [
                        ['text' => 'George Orwell', 'correct' => true],
                        ['text' => 'Aldous Huxley', 'correct' => false],
                        ['text' => 'Ray Bradbury', 'correct' => false],
                        ['text' => 'H.G. Wells', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'In which novel would you find the character Atticus Finch?',
                    'answers' => [
                        ['text' => 'To Kill a Mockingbird', 'correct' => true],
                        ['text' => 'The Catcher in the Rye', 'correct' => false],
                        ['text' => 'The Grapes of Wrath', 'correct' => false],
                        ['text' => 'Of Mice and Men', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Who wrote "The Great Gatsby"?',
                    'answers' => [
                        ['text' => 'F. Scott Fitzgerald', 'correct' => true],
                        ['text' => 'Ernest Hemingway', 'correct' => false],
                        ['text' => 'John Steinbeck', 'correct' => false],
                        ['text' => 'Mark Twain', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which epic poem is attributed to Homer and tells of the Trojan War?',
                    'answers' => [
                        ['text' => 'The Iliad', 'correct' => true],
                        ['text' => 'The Odyssey', 'correct' => false],
                        ['text' => 'Beowulf', 'correct' => false],
                        ['text' => 'The Aeneid', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Who wrote "The Catcher in the Rye"?',
                    'answers' => [
                        ['text' => 'J.D. Salinger', 'correct' => true],
                        ['text' => 'Jack Kerouac', 'correct' => false],
                        ['text' => 'Kurt Vonnegut', 'correct' => false],
                        ['text' => 'Philip Roth', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which novel features the character Jay Gatsby?',
                    'answers' => [
                        ['text' => 'The Great Gatsby', 'correct' => true],
                        ['text' => 'This Side of Paradise', 'correct' => false],
                        ['text' => 'Tender Is the Night', 'correct' => false],
                        ['text' => 'The Sun Also Rises', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Who wrote "Jane Eyre"?',
                    'answers' => [
                        ['text' => 'Charlotte Bronte', 'correct' => true],
                        ['text' => 'Jane Austen', 'correct' => false],
                        ['text' => 'Emily Bronte', 'correct' => false],
                        ['text' => 'George Eliot', 'correct' => false],
                    ],
                ],
            ],
            'Sports' => [
                [
                    'question' => 'How many players are on the field for one soccer team?',
                    'answers' => [
                        ['text' => '11', 'correct' => true],
                        ['text' => '10', 'correct' => false],
                        ['text' => '9', 'correct' => false],
                        ['text' => '12', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which sport is known as "America\'s pastime"?',
                    'answers' => [
                        ['text' => 'Baseball', 'correct' => true],
                        ['text' => 'Basketball', 'correct' => false],
                        ['text' => 'Football', 'correct' => false],
                        ['text' => 'Hockey', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'The Super Bowl is the championship game of which league?',
                    'answers' => [
                        ['text' => 'NFL', 'correct' => true],
                        ['text' => 'NBA', 'correct' => false],
                        ['text' => 'MLB', 'correct' => false],
                        ['text' => 'NHL', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'How many points is a touchdown worth in American football?',
                    'answers' => [
                        ['text' => '6', 'correct' => true],
                        ['text' => '3', 'correct' => false],
                        ['text' => '7', 'correct' => false],
                        ['text' => '8', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which country hosts the Wimbledon tennis tournament?',
                    'answers' => [
                        ['text' => 'United Kingdom', 'correct' => true],
                        ['text' => 'France', 'correct' => false],
                        ['text' => 'Australia', 'correct' => false],
                        ['text' => 'United States', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'In basketball, how many points is a free throw worth?',
                    'answers' => [
                        ['text' => '1', 'correct' => true],
                        ['text' => '2', 'correct' => false],
                        ['text' => '3', 'correct' => false],
                        ['text' => '4', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which sport uses the term "birdie"?',
                    'answers' => [
                        ['text' => 'Golf', 'correct' => true],
                        ['text' => 'Cricket', 'correct' => false],
                        ['text' => 'Badminton', 'correct' => false],
                        ['text' => 'Baseball', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which country won the FIFA World Cup in 2018?',
                    'answers' => [
                        ['text' => 'France', 'correct' => true],
                        ['text' => 'Germany', 'correct' => false],
                        ['text' => 'Brazil', 'correct' => false],
                        ['text' => 'Argentina', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'In which sport would you perform a slam dunk?',
                    'answers' => [
                        ['text' => 'Basketball', 'correct' => true],
                        ['text' => 'Volleyball', 'correct' => false],
                        ['text' => 'Tennis', 'correct' => false],
                        ['text' => 'Hockey', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'How many players are on a standard baseball team in the field?',
                    'answers' => [
                        ['text' => '9', 'correct' => true],
                        ['text' => '8', 'correct' => false],
                        ['text' => '10', 'correct' => false],
                        ['text' => '11', 'correct' => false],
                    ],
                ],
            ],
            'Music' => [
                [
                    'question' => 'Which composer wrote the Fifth Symphony?',
                    'answers' => [
                        ['text' => 'Ludwig van Beethoven', 'correct' => true],
                        ['text' => 'Wolfgang Amadeus Mozart', 'correct' => false],
                        ['text' => 'Johann Sebastian Bach', 'correct' => false],
                        ['text' => 'Franz Schubert', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which instrument has 88 keys?',
                    'answers' => [
                        ['text' => 'Piano', 'correct' => true],
                        ['text' => 'Organ', 'correct' => false],
                        ['text' => 'Harpsichord', 'correct' => false],
                        ['text' => 'Accordion', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Who is known as the "King of Pop"?',
                    'answers' => [
                        ['text' => 'Michael Jackson', 'correct' => true],
                        ['text' => 'Elvis Presley', 'correct' => false],
                        ['text' => 'Prince', 'correct' => false],
                        ['text' => 'Freddie Mercury', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which band released the album "Abbey Road"?',
                    'answers' => [
                        ['text' => 'The Beatles', 'correct' => true],
                        ['text' => 'The Rolling Stones', 'correct' => false],
                        ['text' => 'Pink Floyd', 'correct' => false],
                        ['text' => 'Led Zeppelin', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which singer is known for the song "Like a Virgin"?',
                    'answers' => [
                        ['text' => 'Madonna', 'correct' => true],
                        ['text' => 'Whitney Houston', 'correct' => false],
                        ['text' => 'Celine Dion', 'correct' => false],
                        ['text' => 'Janet Jackson', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'What is the highest female singing voice type?',
                    'answers' => [
                        ['text' => 'Soprano', 'correct' => true],
                        ['text' => 'Alto', 'correct' => false],
                        ['text' => 'Mezzo-soprano', 'correct' => false],
                        ['text' => 'Contralto', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which composer wrote "The Four Seasons"?',
                    'answers' => [
                        ['text' => 'Antonio Vivaldi', 'correct' => true],
                        ['text' => 'Johann Sebastian Bach', 'correct' => false],
                        ['text' => 'George Handel', 'correct' => false],
                        ['text' => 'Joseph Haydn', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which genre is associated with artists like Miles Davis?',
                    'answers' => [
                        ['text' => 'Jazz', 'correct' => true],
                        ['text' => 'Classical', 'correct' => false],
                        ['text' => 'Country', 'correct' => false],
                        ['text' => 'Reggae', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which band is known for the song "Bohemian Rhapsody"?',
                    'answers' => [
                        ['text' => 'Queen', 'correct' => true],
                        ['text' => 'The Who', 'correct' => false],
                        ['text' => 'U2', 'correct' => false],
                        ['text' => 'Aerosmith', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'What instrument is commonly associated with Yo-Yo Ma?',
                    'answers' => [
                        ['text' => 'Cello', 'correct' => true],
                        ['text' => 'Violin', 'correct' => false],
                        ['text' => 'Piano', 'correct' => false],
                        ['text' => 'Clarinet', 'correct' => false],
                    ],
                ],
            ],
            'Art' => [
                [
                    'question' => 'Who painted the "Mona Lisa"?',
                    'answers' => [
                        ['text' => 'Leonardo da Vinci', 'correct' => true],
                        ['text' => 'Michelangelo', 'correct' => false],
                        ['text' => 'Raphael', 'correct' => false],
                        ['text' => 'Donatello', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which artist painted "The Starry Night"?',
                    'answers' => [
                        ['text' => 'Vincent van Gogh', 'correct' => true],
                        ['text' => 'Claude Monet', 'correct' => false],
                        ['text' => 'Pablo Picasso', 'correct' => false],
                        ['text' => 'Edgar Degas', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'The sculpture "David" was created by which artist?',
                    'answers' => [
                        ['text' => 'Michelangelo', 'correct' => true],
                        ['text' => 'Donatello', 'correct' => false],
                        ['text' => 'Bernini', 'correct' => false],
                        ['text' => 'Rodin', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which art movement is Salvador Dali associated with?',
                    'answers' => [
                        ['text' => 'Surrealism', 'correct' => true],
                        ['text' => 'Cubism', 'correct' => false],
                        ['text' => 'Impressionism', 'correct' => false],
                        ['text' => 'Baroque', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which museum houses the painting "The Night Watch"?',
                    'answers' => [
                        ['text' => 'Rijksmuseum', 'correct' => true],
                        ['text' => 'Louvre', 'correct' => false],
                        ['text' => 'Prado', 'correct' => false],
                        ['text' => 'Uffizi', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Who painted "The Persistence of Memory"?',
                    'answers' => [
                        ['text' => 'Salvador Dali', 'correct' => true],
                        ['text' => 'Pablo Picasso', 'correct' => false],
                        ['text' => 'Henri Matisse', 'correct' => false],
                        ['text' => 'Paul Klee', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which artist is famous for the Campbell\'s Soup Cans series?',
                    'answers' => [
                        ['text' => 'Andy Warhol', 'correct' => true],
                        ['text' => 'Roy Lichtenstein', 'correct' => false],
                        ['text' => 'Jackson Pollock', 'correct' => false],
                        ['text' => 'Mark Rothko', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'The painting "Guernica" was created by which artist?',
                    'answers' => [
                        ['text' => 'Pablo Picasso', 'correct' => true],
                        ['text' => 'Francisco Goya', 'correct' => false],
                        ['text' => 'Diego Rivera', 'correct' => false],
                        ['text' => 'Joan Miro', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which style is Claude Monet most associated with?',
                    'answers' => [
                        ['text' => 'Impressionism', 'correct' => true],
                        ['text' => 'Expressionism', 'correct' => false],
                        ['text' => 'Fauvism', 'correct' => false],
                        ['text' => 'Realism', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Who painted the ceiling of the Sistine Chapel?',
                    'answers' => [
                        ['text' => 'Michelangelo', 'correct' => true],
                        ['text' => 'Raphael', 'correct' => false],
                        ['text' => 'Titian', 'correct' => false],
                        ['text' => 'Caravaggio', 'correct' => false],
                    ],
                ],
            ],
            'Technology' => [
                [
                    'question' => 'Who is known as the co-founder of Microsoft?',
                    'answers' => [
                        ['text' => 'Bill Gates', 'correct' => true],
                        ['text' => 'Steve Jobs', 'correct' => false],
                        ['text' => 'Larry Page', 'correct' => false],
                        ['text' => 'Jeff Bezos', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'What does "HTTP" stand for?',
                    'answers' => [
                        ['text' => 'Hypertext Transfer Protocol', 'correct' => true],
                        ['text' => 'Hyperlink Transfer Program', 'correct' => false],
                        ['text' => 'High Transfer Text Process', 'correct' => false],
                        ['text' => 'Hypertext Transport Packet', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which company developed the Android operating system?',
                    'answers' => [
                        ['text' => 'Google', 'correct' => true],
                        ['text' => 'Apple', 'correct' => false],
                        ['text' => 'Microsoft', 'correct' => false],
                        ['text' => 'IBM', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'What does "CPU" stand for?',
                    'answers' => [
                        ['text' => 'Central Processing Unit', 'correct' => true],
                        ['text' => 'Computer Processing Utility', 'correct' => false],
                        ['text' => 'Central Program Unit', 'correct' => false],
                        ['text' => 'Core Processing Unit', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which programming language is primarily used for iOS development?',
                    'answers' => [
                        ['text' => 'Swift', 'correct' => true],
                        ['text' => 'Java', 'correct' => false],
                        ['text' => 'Kotlin', 'correct' => false],
                        ['text' => 'C#', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'What does "RAM" stand for?',
                    'answers' => [
                        ['text' => 'Random Access Memory', 'correct' => true],
                        ['text' => 'Read Access Memory', 'correct' => false],
                        ['text' => 'Rapid Access Module', 'correct' => false],
                        ['text' => 'Remote Access Memory', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which company created the first iPhone?',
                    'answers' => [
                        ['text' => 'Apple', 'correct' => true],
                        ['text' => 'Samsung', 'correct' => false],
                        ['text' => 'Nokia', 'correct' => false],
                        ['text' => 'Motorola', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'What is the main function of a GPU?',
                    'answers' => [
                        ['text' => 'Graphics rendering', 'correct' => true],
                        ['text' => 'Data storage', 'correct' => false],
                        ['text' => 'Network routing', 'correct' => false],
                        ['text' => 'Power management', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which company is known for the Linux kernel mascot, Tux?',
                    'answers' => [
                        ['text' => 'The Linux community', 'correct' => true],
                        ['text' => 'Microsoft', 'correct' => false],
                        ['text' => 'Apple', 'correct' => false],
                        ['text' => 'Oracle', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'What does "URL" stand for?',
                    'answers' => [
                        ['text' => 'Uniform Resource Locator', 'correct' => true],
                        ['text' => 'Universal Record Link', 'correct' => false],
                        ['text' => 'Unified Route Locator', 'correct' => false],
                        ['text' => 'Universal Resource Link', 'correct' => false],
                    ],
                ],
            ],
            'Politics' => [
                [
                    'question' => 'How many terms can a U.S. president serve?',
                    'answers' => [
                        ['text' => 'Two', 'correct' => true],
                        ['text' => 'One', 'correct' => false],
                        ['text' => 'Three', 'correct' => false],
                        ['text' => 'Unlimited', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'The U.S. Congress has how many chambers?',
                    'answers' => [
                        ['text' => 'Two', 'correct' => true],
                        ['text' => 'One', 'correct' => false],
                        ['text' => 'Three', 'correct' => false],
                        ['text' => 'Four', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which branch of U.S. government interprets the law?',
                    'answers' => [
                        ['text' => 'Judicial', 'correct' => true],
                        ['text' => 'Executive', 'correct' => false],
                        ['text' => 'Legislative', 'correct' => false],
                        ['text' => 'Administrative', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'What is the term length for a U.S. Senator?',
                    'answers' => [
                        ['text' => '6 years', 'correct' => true],
                        ['text' => '2 years', 'correct' => false],
                        ['text' => '4 years', 'correct' => false],
                        ['text' => '8 years', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which document begins with "We the People"?',
                    'answers' => [
                        ['text' => 'The U.S. Constitution', 'correct' => true],
                        ['text' => 'The Declaration of Independence', 'correct' => false],
                        ['text' => 'The Federalist Papers', 'correct' => false],
                        ['text' => 'The Bill of Rights', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which body confirms U.S. Supreme Court justices?',
                    'answers' => [
                        ['text' => 'The U.S. Senate', 'correct' => true],
                        ['text' => 'The U.S. House', 'correct' => false],
                        ['text' => 'The President alone', 'correct' => false],
                        ['text' => 'The Supreme Court', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'How many justices are on the U.S. Supreme Court?',
                    'answers' => [
                        ['text' => '9', 'correct' => true],
                        ['text' => '7', 'correct' => false],
                        ['text' => '11', 'correct' => false],
                        ['text' => '13', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'What is the lower house of the U.S. Congress called?',
                    'answers' => [
                        ['text' => 'House of Representatives', 'correct' => true],
                        ['text' => 'Senate', 'correct' => false],
                        ['text' => 'House of Commons', 'correct' => false],
                        ['text' => 'National Assembly', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which amendment guarantees freedom of speech in the U.S.?',
                    'answers' => [
                        ['text' => 'First Amendment', 'correct' => true],
                        ['text' => 'Second Amendment', 'correct' => false],
                        ['text' => 'Fourth Amendment', 'correct' => false],
                        ['text' => 'Tenth Amendment', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which role is second in line of succession to the U.S. President?',
                    'answers' => [
                        ['text' => 'Speaker of the House', 'correct' => true],
                        ['text' => 'Secretary of State', 'correct' => false],
                        ['text' => 'Chief Justice', 'correct' => false],
                        ['text' => 'Attorney General', 'correct' => false],
                    ],
                ],
            ],
            'Pop Culture' => [
                [
                    'question' => 'Which movie features the quote "May the Force be with you"?',
                    'answers' => [
                        ['text' => 'Star Wars', 'correct' => true],
                        ['text' => 'Star Trek', 'correct' => false],
                        ['text' => 'The Matrix', 'correct' => false],
                        ['text' => 'Avatar', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Who played Jack in the movie "Titanic"?',
                    'answers' => [
                        ['text' => 'Leonardo DiCaprio', 'correct' => true],
                        ['text' => 'Brad Pitt', 'correct' => false],
                        ['text' => 'Matt Damon', 'correct' => false],
                        ['text' => 'Johnny Depp', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which TV series features the character Walter White?',
                    'answers' => [
                        ['text' => 'Breaking Bad', 'correct' => true],
                        ['text' => 'The Sopranos', 'correct' => false],
                        ['text' => 'The Wire', 'correct' => false],
                        ['text' => 'Mad Men', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Who is the author of the Harry Potter series?',
                    'answers' => [
                        ['text' => 'J.K. Rowling', 'correct' => true],
                        ['text' => 'Suzanne Collins', 'correct' => false],
                        ['text' => 'Stephenie Meyer', 'correct' => false],
                        ['text' => 'Rick Riordan', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which singer is known for the song "Shape of You"?',
                    'answers' => [
                        ['text' => 'Ed Sheeran', 'correct' => true],
                        ['text' => 'Justin Bieber', 'correct' => false],
                        ['text' => 'Bruno Mars', 'correct' => false],
                        ['text' => 'Adele', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which film won Best Picture at the 2020 Oscars?',
                    'answers' => [
                        ['text' => 'Parasite', 'correct' => true],
                        ['text' => '1917', 'correct' => false],
                        ['text' => 'Joker', 'correct' => false],
                        ['text' => 'Once Upon a Time in Hollywood', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which video game franchise features the character Mario?',
                    'answers' => [
                        ['text' => 'Super Mario', 'correct' => true],
                        ['text' => 'Sonic the Hedgehog', 'correct' => false],
                        ['text' => 'Halo', 'correct' => false],
                        ['text' => 'Final Fantasy', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which band performed the song "Smells Like Teen Spirit"?',
                    'answers' => [
                        ['text' => 'Nirvana', 'correct' => true],
                        ['text' => 'Pearl Jam', 'correct' => false],
                        ['text' => 'Radiohead', 'correct' => false],
                        ['text' => 'Soundgarden', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which streaming service released the series "Stranger Things"?',
                    'answers' => [
                        ['text' => 'Netflix', 'correct' => true],
                        ['text' => 'Hulu', 'correct' => false],
                        ['text' => 'Disney+', 'correct' => false],
                        ['text' => 'Amazon Prime Video', 'correct' => false],
                    ],
                ],
                [
                    'question' => 'Which actor portrays Iron Man in the Marvel films?',
                    'answers' => [
                        ['text' => 'Robert Downey Jr.', 'correct' => true],
                        ['text' => 'Chris Evans', 'correct' => false],
                        ['text' => 'Chris Hemsworth', 'correct' => false],
                        ['text' => 'Mark Ruffalo', 'correct' => false],
                    ],
                ],
            ],
        ];
    }
}
