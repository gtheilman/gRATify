<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnswerReorderRequest;
use App\Http\Requests\StoreAnswerRequest;
use App\Http\Requests\UpdateAnswerRequest;
use App\Models\Answer;
use App\Models\Question;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Handles answer CRUD plus ordering helpers for the editor UI.
 */
class AnswerController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Answer::class, 'answer', ['except' => ['store']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return void
     */
    public function index(): void
    {
        //
    }

    /**
     * Figure out what sequence number to assign
     *
     * @return int
     */
    public function maxAnswerNumber(int $question_id): int
    {
        return (int) (DB::table('answers')
            ->where('question_id', '=', $question_id)
            ->max('sequence') ?? 0);
    }

    /**
     * Get All answers in the question in sequence
     *
     * @param int $question_id
     * @return \Illuminate\Support\Collection<int, \App\Models\Answer>
     */
    public function getAllAnswers(int $question_id): Collection
    {
        return Answer::query()
            ->select(['id', 'question_id', 'sequence'])
            ->where('question_id', $question_id)
            ->orderBy('sequence')
            ->get();
    }

    /**
     * Renumber the answers
     *
     * @param int $question_id
     * @return bool
     */
    public function renumberAnswers(int $question_id): bool
    {

        $answers = $this->getAllAnswers($question_id);

        // Keep sequence numbers contiguous after inserts/deletes/reorders.
        $newNumber = 1;
        foreach ($answers as $answer) {
            $answer->sequence = $newNumber;
            $answer->save();
            $newNumber = $newNumber + 1;
        }

        return true;
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return void
     */
    public function create(): void
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Http\Requests\StoreAnswerRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreAnswerRequest $request): JsonResponse
    {

        $questionId = (int) $request->get('question_id');
        $question = Question::query()
            ->select(['id', 'assessment_id'])
            ->with(['assessment:id,user_id'])
            ->whereKey($questionId)
            ->first();
        if (!$question) {
            return $this->errorResponse('not_found', null, 404);
        }

        $this->authorize('create', new Answer(['question_id' => $question->id, 'question' => $question]));

        $maxAnswerNumber = $this->maxAnswerNumber($questionId);
        $answer = new Answer([
            'answer_text' => $request->answer_text,
            'feedback' => $request->get('feedback'),
            'correct' => $request->boolean('correct'),
            'sequence' => $maxAnswerNumber + 1,
            'question_id' => $questionId,
        ]);

        $answer->save();

        //$question = Question::find($answer->question_id)->first();

        return (new \App\Http\Resources\AnswerResource($answer))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Answer $answer
     * @return void
     */
    public function show(Answer $answer): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Answer $answer
     * @return void
     */
    public function edit(Answer $answer): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Http\Requests\UpdateAnswerRequest $request
     * @param \App\Models\Answer $answer
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateAnswerRequest $request, Answer $answer): JsonResponse
    {
        $question = Question::query()
            ->select(['id', 'assessment_id'])
            ->with(['assessment:id,user_id'])
            ->find($answer->question_id);
        if (!$question) {
            return $this->errorResponse('not_found', null, 404);
        }

        $answer->setRelation('question', $question);
        $this->authorize('update', $answer);

        $answer->answer_text = $request->get('answer_text');
        $answer->sequence = $request->get('sequence') ?? $this->maxAnswerNumber($question->id);
        $answer->feedback = $request->get('feedback') ?? null;

        $answer->correct = $request->boolean('correct');

        $answer->save();


        return (new \App\Http\Resources\AnswerResource($answer))
            ->response()
            ->setStatusCode(200);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Answer $answer
     * @return \Illuminate\Http\Response
     */
    public function destroy(Answer $answer): Response
    {
        $answer->load(['question.assessment:id,user_id']);
        $this->authorize('delete', $answer);

        $answer->delete();
        return response()->noContent();

    }

    /**
     * Move an answer up in sequence.
     */
    public function promote(AnswerReorderRequest $request): JsonResponse
    {
        $answer_id = (int) $request->get('answer_id');
        $question_id = (int) $request->get('question_id');
        $answer = Answer::select(['id', 'question_id', 'sequence'])->find($answer_id);
        if (!$answer) {
            return $this->errorResponse('not_found', null, 404);
        }
        if ($answer->question_id !== $question_id) {
            return response()->json([
                'message' => 'Answer does not belong to question.',
                'errors' => ['question_id' => ['Answer does not belong to question.']],
            ], 422);
        }

        $answer->load(['question.assessment:id,user_id']);
        $this->authorize('update', $answer);

        $oldSequence = $answer->sequence;
        $targetSequence = $oldSequence - 1;
        // Swap just the neighboring answer to reduce writes.
        DB::transaction(function () use ($answer, $question_id, $oldSequence, $targetSequence) {
            $neighbor = Answer::query()
                ->select(['id', 'sequence'])
                ->where('question_id', $question_id)
                ->where('sequence', $targetSequence)
                ->first();
            if (! $neighbor) {
                return;
            }

            $neighbor->sequence = $oldSequence;
            $neighbor->save();

            $answer->sequence = $targetSequence;
            $answer->save();
        });

        return response()->json(['status' => 'Renumbered'], 200);
    }

    /**
     * Move an answer down in sequence.
     */
    public function demote(AnswerReorderRequest $request): JsonResponse
    {
        $answer_id = (int) $request->get('answer_id');
        $question_id = (int) $request->get('question_id');
        $answer = Answer::select(['id', 'question_id', 'sequence'])->find($answer_id);
        if (!$answer) {
            return $this->errorResponse('not_found', null, 404);
        }
        if ($answer->question_id !== $question_id) {
            return response()->json([
                'message' => 'Answer does not belong to question.',
                'errors' => ['question_id' => ['Answer does not belong to question.']],
            ], 422);
        }

        $answer->load(['question.assessment:id,user_id']);
        $this->authorize('update', $answer);

        $oldSequence = $answer->sequence;
        $targetSequence = $oldSequence + 1;
        // Swap just the neighboring answer to reduce writes.
        DB::transaction(function () use ($answer, $question_id, $oldSequence, $targetSequence) {
            $neighbor = Answer::query()
                ->select(['id', 'sequence'])
                ->where('question_id', $question_id)
                ->where('sequence', $targetSequence)
                ->first();
            if (! $neighbor) {
                return;
            }

            $neighbor->sequence = $oldSequence;
            $neighbor->save();

            $answer->sequence = $targetSequence;
            $answer->save();
        });

        return response()->json(['status' => 'Renumbered'], 200);
    }
}
