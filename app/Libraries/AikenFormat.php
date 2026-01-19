<?php


namespace App\Libraries;


// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Aiken format question importer.
 *
 * @package    qformat_aiken
 * @copyright  2003 Tom Robb <tom@robb.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


//defined('MOODLE_INTERNAL') || die();


/**
 * Aiken format - a simple format for creating multiple choice questions (with
 * only one correct choice, and no feedback).
 *
 * The format looks like this:
 *
 * Question text
 * A) Choice #1
 * B) Choice #2
 * C) Choice #3
 * D) Choice #4
 * ANSWER: B
 *
 * That is,
 *  + question text all one one line.
 *  + then a number of choices, one to a line. Each line must comprise a letter,
 *    then ')' or '.', then a space, then the choice text.
 *  + Then a line of the form 'ANSWER: X' to indicate the correct answer.
 *
 * Be sure to word "All of the above" type choices like "All of these" in
 * case choices are being shuffled.
 *
 * @copyright  2003 Tom Robb <tom@robb.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class AikenFormat
{
    private array $errors = [];
    private array $warnings = [];

    public function __construct($path = null, $rawContent = null)
    {
        if (!is_null($rawContent)) {
            // Normalize newlines into an array of lines.
            $this->lines = preg_split('/\r\n|\r|\n/', $rawContent);
        } elseif ($path) {
            $this->lines = file($path);
        } else {
            $this->lines = [];
        }

    }

    public function provide_import()
    {
        return true;
    }

    public function provide_export()
    {
        return true;
    }

    //modification
    public function get_string($error, $class, $linenumber)
    {
        $labels = [
            'questionnotstarted' => 'Question Not Started',
            'questionmissinganswers' => 'Question Missing Answers',
            'questionnotcomplete' => 'Question Not Complete',
            'invalidanswerlabel' => 'Invalid Answer Label',
            'invalidanswerformat' => 'Invalid ANSWER Line',
            'invalidanswerchoice' => 'ANSWER Does Not Match Any Choice',
        ];
        $label = $labels[$error] ?? $error;
        return 'Error:  ' . $label . ' on line ' . $linenumber;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    private function addError(string $message, int $linenumber, ?string $line = null): void
    {
        $detail = $line ? $message . ' (got: ' . $line . ')' : $message;
        $this->errors[] = 'Error: ' . $detail . ' on line ' . $linenumber;
    }

    private function addWarning(string $message, int $linenumber, ?string $line = null): void
    {
        $detail = $line ? $message . ' (got: ' . $line . ')' : $message;
        $this->warnings[] = 'Warning: ' . $detail . ' on line ' . $linenumber;
    }

    public function readquestions()
    {
        $questions = array();
        $question = null;
        $endchar = chr(13);
        $linenumber = 0;
        $lines = $this->lines;
        $choiceLetters = [];
        $sawAnswerLine = false;
        $questionTextLines = [];
        foreach ($lines as $line) {
            $stp = strpos($line, $endchar, 0);
            $newlines = explode($endchar, $line);
            $linescount = count($newlines);
            for ($i = 0; $i < $linescount; $i++) {
                $linenumber++;
                $nowline = trim($newlines[$i]);

                // Go through the array and build an object called $question
                // When done, add $question to $questions.
                if (strlen($nowline) < 2) {
                    if (!is_null($question) && count($choiceLetters) === 0) {
                        $questionTextLines[] = '';
                    }
                    continue;
                }
                if (preg_match('/^[A-Z][).][ \t]?/', $nowline) || preg_match('/^[a-z][).][ \t]?/', $nowline)) {
                    if (is_null($question)) {
                        // We have a response line, but we aren't currently in a question.
                        $this->addError('Answer provided before question text', $linenumber, $nowline);
                        continue;
                    }

                    // A choice. Trim off the label and space, then save.
                    $label = substr($nowline, 0, 1);
                    if ($label !== strtoupper($label)) {
                        $this->addWarning('Answer labels should be uppercase letters A-Z', $linenumber, $nowline);
                    }
                    $label = strtoupper($label);
                    $expectedLabel = chr(ord('A') + count($choiceLetters));
                    if (in_array($label, $choiceLetters, true)) {
                        $this->addError('Duplicate answer label ' . $label, $linenumber, $nowline);
                    } elseif ($label !== $expectedLabel) {
                        $this->addWarning('Missing answer label ' . $expectedLabel, $linenumber, $nowline);
                    }
                    if (!preg_match('/^[A-Z][).]\\s+/', $nowline)) {
                        $this->addWarning('Answer label should be followed by a space', $linenumber, $nowline);
                    }
                    $choiceLetters[] = $label;
                    $answerText = trim(substr($nowline, 2));
                    if ($answerText === '') {
                        $this->addError('Answer text missing', $linenumber, $nowline);
                    } else {
                        $question->answer[] = $this->text_field(
                            htmlspecialchars($answerText, ENT_NOQUOTES));
                    }
                    //$question->fraction[] = 0;
                    //$question->feedback[] = $this->text_field('');
                } else if (preg_match('/^ANSWER:/', $nowline)) {
                    if (is_null($question)) {
                        // We have an answer line, but we aren't currently in a question.
                        $this->addError('ANSWER provided before question text', $linenumber, $nowline);
                        continue;
                    }
                    if (count($choiceLetters) < 1) {
                        $this->addError('ANSWER appears before any answers are listed', $linenumber, $nowline);
                        $question = null;
                        $choiceLetters = [];
                        $sawAnswerLine = false;
                        continue;
                    }
                    if ($sawAnswerLine) {
                        $this->addError('Multiple ANSWER lines found for the same question', $linenumber, $nowline);
                        $question = null;
                        $choiceLetters = [];
                        $sawAnswerLine = false;
                        continue;
                    }
                    $sawAnswerLine = true;

                    // The line that indicates the correct answer. This question is finised.
                    $ans = trim(substr($nowline, strpos($nowline, ':') + 1));
                    if (strlen($ans) > 1) {
                        $this->addError('ANSWER must be a single letter A-Z', $linenumber, $nowline);
                        $question = null;
                        $choiceLetters = [];
                        $sawAnswerLine = false;
                        continue;
                    }
                    $ans = strtoupper(substr($ans, 0, 1));
                    if (!preg_match('/^[A-Z]$/', $ans)) {
                        $this->addError('ANSWER must be a single letter A-Z', $linenumber, $nowline);
                        $question = null;
                        $choiceLetters = [];
                        $sawAnswerLine = false;
                        continue;
                    }
                    // We want to map A to 0, B to 1, etc.
                    $rightans = ord($ans) - ord('A');
                    $question->rightans = $rightans;

                    if (count($question->answer) < 2) {
                        // The multichoice question requires at least 2 answers, or there will be a failure later.
                        $this->addError('Question must include at least 2 answers', $linenumber);
                        $question = null;
                        $choiceLetters = [];
                        $sawAnswerLine = false;
                        continue;
                    }
                    if (count($question->answer) > 26) {
                        $this->addError('Questions may not have more than 26 answers', $linenumber);
                        $question = null;
                        $choiceLetters = [];
                        $sawAnswerLine = false;
                        continue;
                    }
                    if ($choiceLetters && !in_array($ans, $choiceLetters, true)) {
                        $this->addError('ANSWER letter does not match any provided choice', $linenumber, $nowline);
                        $question = null;
                        $choiceLetters = [];
                        $sawAnswerLine = false;
                        continue;
                    }

                    //$question->fraction[$rightans] = 1;
                    $questions[] = $question;

                    // Clear variable for next question set.
                    $question = null;
                    $choiceLetters = [];
                    $sawAnswerLine = false;
                    $questionTextLines = [];
                    continue;
                } else {
                    // Must be the first line of a new question, since no recognised prefix.
                    if (!is_null($question)) {
                        if (count($choiceLetters) === 0) {
                            $questionTextLines[] = $nowline;
                            $question->questiontext = htmlspecialchars(trim(implode("\n", $questionTextLines)), ENT_NOQUOTES);
                            $question->name = $question->questiontext;
                            continue;
                        }
                        // In this case, there was already an open question that we didn't complete. It is being discarded.
                        $this->addError('No correct answer specified', $linenumber);
                    }

                    $question = new \stdClass();
                    //$question->qtype = 'multichoice';
                    //$question->name = $this->create_default_question_name($nowline, get_string('questionname', 'question'));
                    $questionTextLines = [$nowline];
                    $question->questiontext = htmlspecialchars(trim(implode("\n", $questionTextLines)), ENT_NOQUOTES);
                    // Provide a stable name to avoid undefined property notices later in the flow.
                    $question->name = $question->questiontext;
                    $choiceLetters = [];
                    $sawAnswerLine = false;
                    //$question->questiontextformat = FORMAT_HTML;
                    //$question->generalfeedback = '';
                    //$question->generalfeedbackformat = FORMAT_HTML;
                    //$question->single = 1;
                    $question->answer = array();
                    //$question->fraction = array();
                    //$question->feedback = array();
                    //$question->correctfeedback = $this->text_field('');
                    //$question->partiallycorrectfeedback = $this->text_field('');
                    //$question->incorrectfeedback = $this->text_field('');
                }
                if ($question && count($question->answer) > 26) {
                    $this->addError('Questions may not have more than 26 answers', $linenumber);
                }
            }
        }
        if (!is_null($question)) {
            $this->addError('No correct answer specified', $linenumber ?: 1);
        }
        return $questions;
    }


    protected function text_field($text)
    {
        return array(
            'text' => htmlspecialchars(trim($text), ENT_NOQUOTES)
        );
    }

    public function readquestion($lines)
    {
        // This is no longer needed but might still be called by default.php.
        return;
    }

    public function exportpreprocess()
    {
        // This format is not able to export categories.
        $this->setCattofile(false);
        return true;
    }

    public function writequestion($question)
    {
        $endchar = "\n";

        // Only export multichoice questions.
        if ($question->qtype != 'multichoice') {
            return null;
        }

        // Do not export multichoice multi questions.
        if (!$question->options->single) {
            return null;
        }

        // Aiken format is not able to handle question with more than 26 answers.
        if (count($question->options->answers) > 26) {
            return null;
        }

        // Export the question displaying message.
        $expout = str_replace("\n", '', question_utils::to_plain_text($question->questiontext,
                $question->questiontextformat, array('para' => false, 'newlines' => false))) . $endchar;
        $num = 0;
        foreach ($question->options->answers as $answer) {
            $number = chr(ord('A') + $num);
            $expout .= $number . ') ' . str_replace("\n", '', question_utils::to_plain_text($answer->answer,
                    $answer->answerformat, array('para' => false, 'newlines' => false))) . $endchar;
            if ($answer->fraction > .99) {
                $correctanswer = $number;
            }
            $num++;
        }
        // Add the correct answer.
        $expout .= 'ANSWER: ' . $correctanswer;

        return $expout;
    }
}
