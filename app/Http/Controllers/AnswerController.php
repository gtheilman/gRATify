<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnswerRequest;
use App\Http\Requests\UpdateAnswerRequest;
use App\Models\Answer;
use App\Models\Assessment;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnswerController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Answer::class, 'answer', ['except' => ['store']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Figure out what sequence number to assign
     *
     * @return integer
     */
    public function maxAnswerNumber($question_id)
    {
        return DB::table('answers')
            ->where('question_id', '=', $question_id)
            ->max('sequence');
    }

    /**
     * Get All answers in the question in sequence
     *
     * @param integer $question_id
     * @return
     */
    public function getAllAnswers($question_id)
    {
        $answers = Answer::all()
            ->where('question_id', $question_id);
        return $answers->sortBy('sequence');
    }

    /**
     * Renumber the answers
     *
     * @param integer $question_id
     * @return boolean
     */
    public function renumberAnswers($question_id)
    {

        $answers = $this->getAllAnswers($question_id);

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
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    public function store(StoreAnswerRequest $request)
    {

        $question = Question::find($request->question_id);
        if (!$question) {
            return response()->json(['status' => 'Not Found'], 404);
        }

        $this->authorize('create', new Answer(['question_id' => $question->id, 'question' => $question]));

        $maxAnswerNumber = $this->maxAnswerNumber($request->question_id);
        $answer = new Answer([
            'answer_text' => $request->answer_text,
            'feedback' => $request->get('feedback'),
            'correct' => $request->boolean('correct'),
            'sequence' => $maxAnswerNumber + 1,
            'question_id' => $question->id,
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
     * @param \App\Answer $answer
     * @return \Illuminate\Http\Response
     */
    public function show(Answer $answer)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Answer $answer
     * @return \Illuminate\Http\Response
     */
    public function edit(Answer $answer)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param
     * @return string
     */
    public function update(UpdateAnswerRequest $request, Answer $answer)
    {
        $question = Question::find($answer->question_id);
        if (!$question) {
            return response()->json(['status' => 'Not Found'], 404);
        }

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
     * @param integer $answer_id
     * @return string
     */
    public function destroy(Answer $answer)
    {
        $this->authorize('delete', $answer);

        $answer->delete();
        return response()->noContent();

    }

    /**
     * Move an answer up in sequence.
     */
    public function promote(\App\Http\Requests\AnswerReorderRequest $request)
    {
        $answer_id = $request->get('answer_id');
        $question_id = $request->get('question_id');
        $answer = Answer::find($answer_id);
        if (!$answer) {
            return response()->json(['status' => 'Not Found'], 404);
        }
        if ($answer->question_id !== (int) $question_id) {
            return response()->json([
                'message' => 'Answer does not belong to question.',
                'errors' => ['question_id' => ['Answer does not belong to question.']],
            ], 422);
        }

        $this->authorize('update', $answer);

        $oldSequence = $answer->sequence;
        $answers = $this->getAllAnswers($question_id);

        foreach ($answers as $current) {
            if ($current->sequence == ($oldSequence - 1)) {
                $current->sequence = $current->sequence + 1;
            } else if ($current->sequence == $oldSequence) {
                $current->sequence = $current->sequence - 1;
            }
            $current->save();
        }

        return response()->json(['status' => 'Renumbered'], 200);
    }

    /**
     * Move an answer down in sequence.
     */
    public function demote(\App\Http\Requests\AnswerReorderRequest $request)
    {
        $answer_id = $request->get('answer_id');
        $question_id = $request->get('question_id');
        $answer = Answer::find($answer_id);
        if (!$answer) {
            return response()->json(['status' => 'Not Found'], 404);
        }
        if ($answer->question_id !== (int) $question_id) {
            return response()->json([
                'message' => 'Answer does not belong to question.',
                'errors' => ['question_id' => ['Answer does not belong to question.']],
            ], 422);
        }

        $this->authorize('update', $answer);

        $oldSequence = $answer->sequence;
        $answers = $this->getAllAnswers($question_id);

        foreach ($answers as $current) {
            if ($current->sequence == ($oldSequence + 1)) {
                $current->sequence = $current->sequence - 1;
            } else if ($current->sequence == $oldSequence) {
                $current->sequence = $current->sequence + 1;
            }
            $current->save();
        }

        return response()->json(['status' => 'Renumbered'], 200);
    }
}
