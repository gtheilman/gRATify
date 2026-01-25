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
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

/**
 * Records attempts; prevents duplicate attempts per presentation/answer.
 */
class AttemptController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:web', ['except' => ['store', 'bulkStore']]);
        $this->authorizeResource(Attempt::class, 'attempt', ['except' => ['store', 'bulkStore']]);
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
        $debug = $request->boolean('debug') || $request->header('X-Debug') === '1';
        $queryCount = 0;
        $queryMs = 0.0;
        $start = microtime(true);
        if ($debug) {
            DB::listen(function ($query) use (&$queryCount, &$queryMs) {
                $queryCount += 1;
                $queryMs += $query->time;
            });
        }
        $presentationId = (int) $request->get('presentation_id');
        $answerId = (int) $request->get('answer_id');
        $presentationExists = Presentation::whereKey($presentationId)->exists();
        $answer = Answer::select(['id', 'correct'])->find($answerId);
        if (! $presentationExists || ! $answer) {
            return $this->errorResponse('not_found', null, 404);
        }

        $inserted = Attempt::insertOrIgnore([[
            'presentation_id' => $presentationId,
            'answer_id' => $answerId,
            'points' => $request->get('points'),
            'created_at' => now(),
            'updated_at' => now(),
        ]]);

        if ($inserted === 0) {
            // Preserve idempotent behavior for repeated submissions.
            $payload = [
                'correct' => false,
                'alreadyAttempted' => true,
            ];
            if ($debug) {
                $payload['debug'] = [
                    'server_ms' => (int) round((microtime(true) - $start) * 1000),
                    'db_ms' => (int) round($queryMs),
                    'queries' => $queryCount,
                ];
            }
            return response()->json($payload, 200);
        }

        $isCorrect = (bool) $answer->correct;

        $payload = [
            'correct' => $isCorrect,
            'alreadyAttempted' => false,
        ];
        if ($debug) {
            $payload['debug'] = [
                'server_ms' => (int) round((microtime(true) - $start) * 1000),
                'db_ms' => (int) round($queryMs),
                'queries' => $queryCount,
            ];
        }

        return response()->json($payload, 201);
    }

    /**
     * Store multiple attempts in a single request.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkStore(Request $request): JsonResponse
    {
        $debug = $request->boolean('debug') || $request->header('X-Debug') === '1';
        $queryCount = 0;
        $queryMs = 0.0;
        $start = microtime(true);
        if ($debug) {
            DB::listen(function ($query) use (&$queryCount, &$queryMs) {
                $queryCount += 1;
                $queryMs += $query->time;
            });
        }

        $items = $request->input('attempts');
        if (!is_array($items)) {
            return response()->json(['error' => 'invalid_attempts'], 422);
        }

        $normalized = [];
        foreach ($items as $index => $item) {
            $presentationId = (int) ($item['presentation_id'] ?? 0);
            $answerId = (int) ($item['answer_id'] ?? 0);
            $normalized[] = [
                'index' => $index,
                'presentation_id' => $presentationId,
                'answer_id' => $answerId,
                'valid' => $presentationId > 0 && $answerId > 0,
            ];
        }

        $presentationIds = collect($normalized)
            ->filter(fn ($item) => $item['valid'])
            ->pluck('presentation_id')
            ->unique()
            ->values()
            ->all();
        $answerIds = collect($normalized)
            ->filter(fn ($item) => $item['valid'])
            ->pluck('answer_id')
            ->unique()
            ->values()
            ->all();

        $presentationSet = Presentation::whereIn('id', $presentationIds)->pluck('id')->flip();
        $answers = Answer::whereIn('id', $answerIds)->get(['id', 'correct'])->keyBy('id');
        $existing = Attempt::whereIn('presentation_id', $presentationIds)
            ->whereIn('answer_id', $answerIds)
            ->get(['presentation_id', 'answer_id'])
            ->mapWithKeys(function ($attempt) {
                return ["{$attempt->presentation_id}|{$attempt->answer_id}" => true];
            });

        $now = now();
        $results = [];
        $newAttempts = [];
        $seen = [];

        foreach ($normalized as $item) {
            $presentationId = $item['presentation_id'];
            $answerId = $item['answer_id'];
            $key = "{$presentationId}|{$answerId}";

            if (!$item['valid']) {
                $results[] = [
                    'presentation_id' => $presentationId,
                    'answer_id' => $answerId,
                    'status' => 'invalid',
                    'alreadyAttempted' => false,
                ];
                continue;
            }
            if (!$presentationSet->has($presentationId) || !$answers->has($answerId)) {
                $results[] = [
                    'presentation_id' => $presentationId,
                    'answer_id' => $answerId,
                    'status' => 'not_found',
                    'alreadyAttempted' => false,
                ];
                continue;
            }
            if ($existing->has($key) || isset($seen[$key])) {
                $results[] = [
                    'presentation_id' => $presentationId,
                    'answer_id' => $answerId,
                    'status' => 'already_attempted',
                    'alreadyAttempted' => true,
                    'correct' => false,
                ];
                continue;
            }

            $seen[$key] = true;
            $newAttempts[] = [
                'presentation_id' => $presentationId,
                'answer_id' => $answerId,
                'points' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $results[] = [
                'presentation_id' => $presentationId,
                'answer_id' => $answerId,
                'status' => 'created',
                'alreadyAttempted' => false,
                'correct' => (bool) $answers[$answerId]->correct,
            ];
        }

        if (!empty($newAttempts)) {
            DB::transaction(function () use ($newAttempts) {
                Attempt::insertOrIgnore($newAttempts);
            });
        }

        $payload = ['results' => $results];
        if ($debug) {
            $payload['debug'] = [
                'server_ms' => (int) round((microtime(true) - $start) * 1000),
                'db_ms' => (int) round($queryMs),
                'queries' => $queryCount,
            ];
        }

        return response()->json($payload, 200);
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
