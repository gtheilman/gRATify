<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttemptRequest;
use App\Http\Requests\UpdateAttemptRequest;
use App\Http\Resources\AttemptResource;
use App\Models\Answer;
use App\Models\Attempt;
use App\Models\Presentation;
use Illuminate\Http\Request;

class AttemptController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Attempt::class, 'attempt', ['except' => ['store']]);
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
     * @return boolean
     */
    public function store(StoreAttemptRequest $request)
    {
        $presentation = Presentation::find($request->get('presentation_id'));
        $answer = Answer::find($request->get('answer_id'));
        if (!$presentation || !$answer) {
            return response()->json(['status' => 'Not Found'], 404);
        }

        $attemptQuery = Attempt::where('presentation_id', $request->get('presentation_id'))
            ->where('answer_id', $request->get('answer_id'));

        $alreadyAttempted = $attemptQuery->exists();
        if ($alreadyAttempted) {
            return response()->json([
                'correct' => false,
                'alreadyAttempted' => true,
            ], 200);
        }

        $attempt = new Attempt([
            'presentation_id' => $presentation->id,
            'answer_id' => $answer->id,
            'points' => $request->get('points'),
        ]);
        $attempt->save();

        $answer = Answer::find($attempt->answer_id);
        $isCorrect = $answer ? (bool) $answer->correct : false;

        return response()->json([
            'correct' => $isCorrect,
            'alreadyAttempted' => false,
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateAttemptRequest $request, Attempt $attempt)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'Unauthenticated'], 401);
        }

        $this->authorize('update', $attempt);

        $attempt->presentation_id = $request->get('presentation_id');
        $attempt->answer_id = $request->get('answer_id');
        $attempt->points = $request->get('points');
        $attempt->save();

        return new AttemptResource($attempt);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Attempt $attempt)
    {
        $this->authorize('delete', $attempt);

        $attempt->delete();
        return response()->noContent();
    }
}
