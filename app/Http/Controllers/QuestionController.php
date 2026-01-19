<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuestionRequest;
use App\Http\Requests\QuestionReorderRequest;
use App\Http\Requests\UpdateQuestionRequest;
use App\Models\Answer;
use App\Models\Assessment;
use App\Libraries\AikenFormat;
use App\Models\Presentation;
use App\Models\Question;
use App\Http\Resources\QuestionResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuestionController extends Controller
{

    public $assessment_id;
    public $question_id;

    public function __construct()
    {
        $this->authorizeResource(Question::class, 'question', ['except' => ['store']]);
    }

    /**
     * Find the highest numbered question in an assessment
     *
     * @param integer $assessment_id
     * @return integer
     */
    public function maxQuestionNumber($assessment_id)
    {
        return DB::table('questions')
            ->where('assessment_id', '=', $assessment_id)
            ->max('sequence');
    }


    /**
     * Get All questions in the asssement in sequency
     *
     * @param integer $assessment_id
     * @return
     */
    public function getAllQuestions($assessment_id)
    {
        $questions = Question::all()
            ->where('assessment_id', $assessment_id);
        return $questions->sortBy('sequence');
    }


    /**
     * Renumber the questions
     *
     * @param integer $assessment_id
     * @return boolean
     */
    public function renumberQuestions($assessment_id)
    {

        $questions = $this->getAllQuestions($assessment_id);
        $questions = $questions->sortBy('sequence');

        $newNumber = 1;
        foreach ($questions as $question) {
            $question->sequence = $newNumber;
            $question->save();
            $newNumber = $newNumber + 1;
        }

        return true;
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {      // TODO Figure out if I should delete this for security
        return response()->json(['message' => 'Not implemented.'], 501);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('questions.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreQuestionRequest $request)
    {
        // Resolve the authenticated user via the session guard.
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'Unauthenticated'], 401);
        }

        $assessmentId = $request->get('assessment_id');
        $assessment = Assessment::find($assessmentId);
        if (!$assessment) {
            return response()->json(['status' => 'Not Found'], 404);
        }

        $this->authorize('create', [Question::class, $assessment]);

        $question = new Question([
            'title' => $request->get('title'),
            'stem' => $request->get('stem'),
            'points_possible' => $request->get('points_possible')

        ]);

        $question->assessment_id = $assessmentId;

        $maxQuestionNumber = $this->maxQuestionNumber($question->assessment_id);
        if ($maxQuestionNumber) {
            $question->sequence = $maxQuestionNumber + 1;
        } else {
            $question->sequence = 0;
        }


        $question->save();
        $this->renumberQuestions($question->assessment_id);
        return (new QuestionResource($question))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Question $question
     * @return \Illuminate\Http\Response
     */
    public function show(Question $question)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Question $question
     * @return \Illuminate\Http\Response
     *
     */
    public function edit(Question $question)
    {
        // return view('/assessments/' . $question->assessment_id . '/edit', compact('question'));
        return true;

    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param
     * @return string
     */
    public function update(UpdateQuestionRequest $request, Question $question)
    {
        $assessment = Assessment::find($question->assessment_id);
        if (!$assessment) {
            return response()->json(['status' => 'Not Found'], 404);
        }

        // Resolve authenticated user
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'Unauthenticated'], 401);
        }

        $this->authorize('update', $question);

        // Lock edits if responses exist
        $hasResponses = Presentation::where('assessment_id', $assessment->id)->exists();
        if ($hasResponses) {
            return response()->json(['status' => 'Locked'], 403);
        }

        $question->title = $request->get('title');
        $question->stem = $request->get('stem');
        $question->sequence = $request->get('sequence') ?? $this->maxQuestionNumber($assessment->id);

        $question->save();
        $this->renumberQuestions($question->assessment_id);
        return new QuestionResource($question);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param integer $question_id
     * @return string
     */
    public function destroy(Request $request, Question $question)
    {
        $assessment = Assessment::find($question->assessment_id);

        if (!$assessment) {
            return response()->json(['status' => 'Not Found'], 404);
        }

        // Lock deletes if responses exist
        $hasResponses = Presentation::where('assessment_id', $assessment->id)->exists();
        if ($hasResponses) {
            return response()->json(['status' => 'Locked'], 403);
        }

        // Resolve the authenticated user via the session guard.
        $user = $request->user();

        if (!$user) {
            return response()->json(['status' => 'Not Deleted', 'message' => 'Unauthenticated'], 401);
        }

        $this->authorize('delete', $question);

        $question->delete();
        $this->renumberQuestions($assessment->id);
        return response()->noContent();

    }

    /**
     * Renumber a question to a higher (actually, lower) sequence value
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function promote(QuestionReorderRequest $request)
    {
        $data = $request->validated();
        $question_id = $data['question_id'];
        $assessment_id = $data['assessment_id'];
        $promoteQuestion = Question::find($question_id);
        if (! $promoteQuestion || $promoteQuestion->assessment_id !== (int) $assessment_id) {
            return response()->json([
                'message' => 'Question does not belong to assessment.',
                'errors' => ['assessment_id' => ['Question does not belong to assessment.']],
            ], 422);
        }
        $oldSequence = $promoteQuestion->sequence;

        $questions = $this->getAllQuestions($assessment_id);

        foreach ($questions as $currentQuestion) {
            if ($currentQuestion->sequence == ($oldSequence - 1)) {
                $currentQuestion->sequence = $currentQuestion->sequence + 1;
            } else if ($currentQuestion->sequence == ($oldSequence)) {
                $currentQuestion->sequence = $currentQuestion->sequence - 1;
            }
            $currentQuestion->save();
        }
        return response()->json(['status' => 'Renumbered'], 200);

    }

    /**
     * Renumber a question to a lower (actually, higher) sequence value
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function demote(QuestionReorderRequest $request)
    {
        $data = $request->validated();
        $question_id = $data['question_id'];
        $assessment_id = $data['assessment_id'];
        $promoteQuestion = Question::find($question_id);
        if (! $promoteQuestion || $promoteQuestion->assessment_id !== (int) $assessment_id) {
            return response()->json([
                'message' => 'Question does not belong to assessment.',
                'errors' => ['assessment_id' => ['Question does not belong to assessment.']],
            ], 422);
        }
        $oldSequence = $promoteQuestion->sequence;

        $questions = $this->getAllQuestions($assessment_id);
        foreach ($questions as $currentQuestion) {
            if ($currentQuestion->sequence == ($oldSequence + 1)) {
                $currentQuestion->sequence = $currentQuestion->sequence - 1;
            } else if ($currentQuestion->sequence == ($oldSequence)) {
                $currentQuestion->sequence = $currentQuestion->sequence + 1;
            }
            $currentQuestion->save();
        }

        return response()->json(['status' => 'Renumbered'], 200);

    }


    ///    AIKEN Format Related Functions

    /**
     * Parse the aiken file into an array
     *
     * @param string $content Raw file contents
     * @return array  The aiken file in an array
     */
    public function parseAikenToArray(string $content): array
    {
        $aiken = new AikenFormat(null, $content);

        return [
            'questions' => $aiken->readquestions(),
            'errors' => $aiken->getErrors(),
            'warnings' => $aiken->getWarnings(),
        ];

    }

    /**
     * Write aiken array to database
     * The moodle functions return a standard object with arrays
     *
     * @param array $questionArray The array with the questions
     * @return boolean  success or not
     *
     */
    function writeAikenToDb($questionArray)
    {

        $maxQuestionNumber = $this->maxQuestionNumber($this->assessment_id);

        try {
            foreach ($questionArray as $item) {
                $question = new Question;
                //var_dump($item);
                $question->title = $item->questiontext;
                $question->stem = $item->questiontext;
                $question->assessment_id = $this->assessment_id;
                $maxQuestionNumber = $maxQuestionNumber + 1;
                $question->sequence = $maxQuestionNumber;
                $question->save();

                $rightans = $item->rightans;

                for ($index = 0; $index < count($item->answer); $index++) {
                    $answer = new Answer;
                    $answer->answer_text = $item->answer[$index]['text'];
                    $answer->sequence = $index + 1;
                    if ($index == $rightans) {
                        $answer->correct = 1;
                    }
                    $answer->question_id = $question->id;
                    $answer->save();
                }
            }

        } catch (Throwable $e) {
            report($e);

            return false;
        }

        $this->renumberQuestions($question->assessment_id);
        return true;

    }


    /**
     * Receive the uploaded assessment file, store the path and the file extension
     *
     * @param Request $request
     * @return null
     */
    public function storeUpload(Request $request)
    {
        $validated = $request->validate([
            'assessment' => 'required|file|mimetypes:text/plain,application/octet-stream,text/markdown,text/plain,text/x-log|max:1024',
            'assessment_id' => 'required|integer|exists:assessments,id',
        ]);

        $file = $validated['assessment'];
        $content = $file->get();

        // $this->path = $path;
        $this->assessment_id = $validated['assessment_id'];


        $parsed = $this->parseAikenToArray($content);
        $questionArray = $parsed['questions'] ?? [];
        $errors = $parsed['errors'] ?? [];
        $warnings = $parsed['warnings'] ?? [];
        if ($errors && count($errors)) {
            return response()->json([
                'status' => 'Invalid or unreadable Aiken file',
                'errors' => $errors,
            ], 422);
        }
        if (!$questionArray || !count($questionArray)) {
            return response()->json([
                'status' => 'Invalid or unreadable Aiken file',
                'errors' => $errors,
            ], 422);
        }

        if ($this->writeAikenToDb($questionArray)) {
            //var_dump("processed");
            $insert_status = 'Saved!';
            return response()->json([
                'status' => $insert_status,
                'warnings' => $warnings,
            ], 200);
        } else {
            $insert_status = 'Not Saved!';
            return response()->json(['status' => $insert_status], 500);
        }
        // return redirect('/assessments/' . $this->assessment_id . '/edit')->with('success', $insert_status);
        return true;
    }


}
