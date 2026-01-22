<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttemptRequest;
use App\Http\Requests\UpdateAttemptRequest;
use App\Http\Resources\AttemptResource;
use App\Models\Answer;
use App\Models\Attempt;
use App\Models\Presentation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Records attempts; prevents duplicate attempts per presentation/answer.
 */
class AttemptController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:web', ['except' => ['store']]);
        $this->authorizeResource(Attempt::class, 'attempt', ['except' => ['store']]);
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
     * @param \App\Http\Requests\StoreAttemptRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreAttemptRequest $request): JsonResponse
    {
        $presentationId = (int) $request->get('presentation_id');
        $answerId = (int) $request->get('answer_id');
        $presentation = Presentation::find($presentationId);
        $answer = Answer::find($answerId);
        if (!$presentation || !$answer) {
            return $this->errorResponse('not_found', null, 404);
        }

        $attemptQuery = Attempt::where('presentation_id', $presentationId)
            ->where('answer_id', $answerId);

        $alreadyAttempted = $attemptQuery->exists();
        if ($alreadyAttempted) {
            // Preserve idempotent behavior for repeated submissions.
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
     * @param \App\Models\Attempt $attempt
     * @return void
     */
    public function show(Attempt $attempt): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Attempt $attempt
     * @return void
     */
    public function edit(Attempt $attempt): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Http\Requests\UpdateAttemptRequest $request
     * @param \App\Models\Attempt $attempt
     * @return \App\Http\Resources\AttemptResource
     */
    public function update(UpdateAttemptRequest $request, Attempt $attempt): AttemptResource
    {
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
     * @param \App\Models\Attempt $attempt
     * @return \Illuminate\Http\Response
     */
    public function destroy(Attempt $attempt): Response
    {
        $this->authorize('delete', $attempt);

        $attempt->delete();
        return response()->noContent();
    }
}
