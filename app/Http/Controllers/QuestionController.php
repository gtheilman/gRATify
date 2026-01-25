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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Handles question CRUD plus Aiken file imports and ordering helpers.
 */
class QuestionController extends Controller
{

    public int $assessment_id;
    public ?int $question_id = null;

    public function __construct()
    {
        $this->middleware('auth:web');
        $this->authorizeResource(Question::class, 'question', ['except' => ['store']]);
    }

    /**
     * Find the highest numbered question in an assessment
     *
     * @param int $assessment_id
     * @return int
     */
    public function maxQuestionNumber(int $assessment_id): int
    {
        return (int) (DB::table('questions')
            ->where('assessment_id', '=', $assessment_id)
            ->max('sequence') ?? 0);
    }


    /**
     * Get All questions in the asssement in sequency
     *
     * @param int $assessment_id
     * @return \Illuminate\Support\Collection<int, \App\Models\Question>
     */
    public function getAllQuestions(int $assessment_id): Collection
    {
        return Question::query()
            ->select(['id', 'assessment_id', 'sequence'])
            ->where('assessment_id', $assessment_id)
            ->orderBy('sequence')
            ->get();
    }


    /**
     * Renumber the questions
     *
     * @param int $assessment_id
     * @return bool
     */
    public function renumberQuestions(int $assessment_id): bool
    {

        $questions = $this->getAllQuestions($assessment_id);
        $questions = $questions->sortBy('sequence');

        // Keep sequence numbers contiguous after inserts/deletes/reorders.
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {      // TODO Figure out if I should delete this for security
        return response()->json(['message' => 'Not implemented.'], 501);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create(): \Illuminate\View\View
    {
        /** @var view-string $view */
        $view = 'questions.create';
        return view($view);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Http\Requests\StoreQuestionRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreQuestionRequest $request): JsonResponse
    {
        $assessmentId = (int) $request->get('assessment_id');
        $assessment = Assessment::select(['id', 'user_id'])->find($assessmentId);
        if (!$assessment) {
            return $this->errorResponse('not_found', null, 404);
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
        $question->refresh();
        return (new QuestionResource($question))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Question $question
     * @return void
     */
    public function show(Question $question): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Question $question
     * @return bool
     *
     */
    public function edit(Question $question): bool
    {
        // return view('/assessments/' . $question->assessment_id . '/edit', compact('question'));
        return true;

    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Http\Requests\UpdateQuestionRequest $request
     * @param \App\Models\Question $question
     * @return \App\Http\Resources\QuestionResource|\Illuminate\Http\JsonResponse
     */
    public function update(UpdateQuestionRequest $request, Question $question): QuestionResource|JsonResponse
    {
        $assessment = Assessment::select(['id', 'user_id'])->find($question->assessment_id);
        if (!$assessment) {
            return $this->errorResponse('not_found', null, 404);
        }

        $question->setRelation('assessment', $assessment);
        $this->authorize('update', $question);

        // Lock edits once students have responded.
        $hasResponses = Presentation::where('assessment_id', $assessment->id)->exists();
        if ($hasResponses) {
            return $this->errorResponse('locked', null, 403);
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
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Question $question
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, Question $question): Response|JsonResponse
    {
        $assessment = Assessment::select(['id', 'user_id'])->find($question->assessment_id);

        if (!$assessment) {
            return $this->errorResponse('not_found', null, 404);
        }

        // Lock deletes once students have responded.
        $hasResponses = Presentation::where('assessment_id', $assessment->id)->exists();
        if ($hasResponses) {
            return $this->errorResponse('locked', null, 403);
        }

        $question->setRelation('assessment', $assessment);
        $this->authorize('delete', $question);

        $question->delete();
        $this->renumberQuestions($assessment->id);
        return response()->noContent();

    }

    /**
     * Renumber a question to a higher (actually, lower) sequence value
     *
     * @param \App\Http\Requests\QuestionReorderRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function promote(QuestionReorderRequest $request): JsonResponse
    {
        $data = $request->validated();
        $question_id = (int) $data['question_id'];
        $assessment_id = (int) $data['assessment_id'];
        $promoteQuestion = Question::select(['id', 'assessment_id', 'sequence'])->find($question_id);
        if (! $promoteQuestion || $promoteQuestion->assessment_id !== $assessment_id) {
            return response()->json([
                'message' => 'Question does not belong to assessment.',
                'errors' => ['assessment_id' => ['Question does not belong to assessment.']],
            ], 422);
        }
        $oldSequence = $promoteQuestion->sequence;
        $targetSequence = $oldSequence - 1;
        // Swap just the neighboring question to reduce writes.
        DB::transaction(function () use ($promoteQuestion, $assessment_id, $oldSequence, $targetSequence) {
            $neighbor = Question::query()
                ->select(['id', 'sequence'])
                ->where('assessment_id', $assessment_id)
                ->where('sequence', $targetSequence)
                ->first();
            if (! $neighbor) {
                return;
            }

            $neighbor->sequence = $oldSequence;
            $neighbor->save();

            $promoteQuestion->sequence = $targetSequence;
            $promoteQuestion->save();
        });
        return response()->json(['status' => 'Renumbered'], 200);

    }

    /**
     * Renumber a question to a lower (actually, higher) sequence value
     *
     * @param \App\Http\Requests\QuestionReorderRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function demote(QuestionReorderRequest $request): JsonResponse
    {
        $data = $request->validated();
        $question_id = (int) $data['question_id'];
        $assessment_id = (int) $data['assessment_id'];
        $promoteQuestion = Question::select(['id', 'assessment_id', 'sequence'])->find($question_id);
        if (! $promoteQuestion || $promoteQuestion->assessment_id !== $assessment_id) {
            return response()->json([
                'message' => 'Question does not belong to assessment.',
                'errors' => ['assessment_id' => ['Question does not belong to assessment.']],
            ], 422);
        }
        $oldSequence = $promoteQuestion->sequence;
        $targetSequence = $oldSequence + 1;
        // Swap just the neighboring question to reduce writes.
        DB::transaction(function () use ($promoteQuestion, $assessment_id, $oldSequence, $targetSequence) {
            $neighbor = Question::query()
                ->select(['id', 'sequence'])
                ->where('assessment_id', $assessment_id)
                ->where('sequence', $targetSequence)
                ->first();
            if (! $neighbor) {
                return;
            }

            $neighbor->sequence = $oldSequence;
            $neighbor->save();

            $promoteQuestion->sequence = $targetSequence;
            $promoteQuestion->save();
        });

        return response()->json(['status' => 'Renumbered'], 200);

    }


    ///    AIKEN Format Related Functions

    /**
     * Parse the aiken file into an array
     *
     * @param string $content Raw file contents
     * @return array{questions: array<int, mixed>, errors: array<int, mixed>, warnings: array<int, mixed>}  The aiken file in an array
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
     * @param array<int, mixed> $questionArray The array with the questions
     * @return bool  success or not
     *
     */
    public function writeAikenToDb(array $questionArray): bool
    {

        $maxQuestionNumber = $this->maxQuestionNumber($this->assessment_id);
        $question = null;

        try {
            foreach ($questionArray as $item) {
                if (
                    !is_object($item)
                    || !isset($item->questiontext, $item->rightans, $item->answer)
                    || !is_array($item->answer)
                ) {
                    continue;
                }
                /** @var object{questiontext: string, rightans: int, answer: array<int, array{text: string}>} $item */
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
                        $answer->correct = true;
                    }
                    $answer->question_id = $question->id;
                    $answer->save();
                }
            }

        } catch (\Throwable $e) {
            report($e);

            return false;
        }

        $renumberAssessmentId = $question ? $question->assessment_id : $this->assessment_id;
        $this->renumberQuestions($renumberAssessmentId);
        return true;

    }


    /**
     * Receive the uploaded assessment file, store the path and the file extension
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeUpload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'assessment' => 'required|file|mimetypes:text/plain,application/octet-stream,text/markdown,text/plain,text/x-log|max:1024',
            'assessment_id' => 'required|integer|exists:assessments,id',
        ]);

        $file = $validated['assessment'];
        $content = $file->get();

        // $this->path = $path;
        $this->assessment_id = $validated['assessment_id'];


        // Aiken parsing returns question arrays plus detailed errors/warnings.
        $parsed = $this->parseAikenToArray($content);
        $questionArray = $parsed['questions'];
        $errors = $parsed['errors'];
        $warnings = $parsed['warnings'];
        if (!empty($errors)) {
            return response()->json([
                'status' => 'Invalid or unreadable Aiken file',
                'errors' => $errors,
            ], 422);
        }
        if (empty($questionArray)) {
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
    }


}
