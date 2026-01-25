<?php

namespace App\Http\Controllers;

use App\Http\Resources\AssessmentResource;
use App\Http\Resources\AssessmentEditResource;
use App\Http\Requests\UpdateAssessmentInlineRequest;
use App\Models\Assessment;
use App\Models\Presentation;
use App\Http\Requests\StoreAssessmentRequest;
use App\Http\Requests\UpdateAssessmentRequest;
use App\Services\Shortlinks\ShortlinkService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * CRUD endpoints for assessments, including edit payload shaping for the SPA.
 */
class AssessmentCrudController extends Controller
{
    public function __construct(private ShortlinkService $shortlinks)
    {
        $this->middleware('auth:web');
        $this->authorizeResource(Assessment::class, 'assessment');
    }

    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $role = $request->user()->role ?? 'user';
        $cacheKey = "assessments.index.{$userId}.{$role}";
        $cached = cache()->get($cacheKey);
        if ($cached !== null) {
            return response()->json($cached);
        }
        $baseQuery = Assessment::query()
            ->select([
                'id',
                'user_id',
                'title',
                'course',
                'memo',
                'active',
                'short_url',
                'password',
                'scheduled_at',
                'created_at',
            ])
            ->with(['user:id,username'])
            ->withCount('presentations')
            ->orderByDesc('created_at');

        $user = auth('web')->user();

        if ($userId == config('grat.admin_id') || ($user && $user->role === 'admin')) {
            $assessments = $baseQuery->get();
        } else {
            $assessments = $baseQuery
                ->where('user_id', $userId)
                ->get();
        }

        if (isset($assessments->scheduled_at)) {
            $assessments->scheduled_at = date_format($assessments->scheduled_at, 'Y-m-d');
        }

        $payload = AssessmentResource::collection($assessments)->resolve();
        cache()->put($cacheKey, $payload, 2);
        return response()->json($payload);
    }

    public function listAssessmentsByUser(int $user_id): JsonResponse
    {
        $user = auth('web')->user();

        if (auth('web')->id() == config('grat.admin_id') || ($user && $user->role === 'admin')) {
            $actorId = $user->id ?? 'guest';
            $cacheKey = "assessments.by-user.{$actorId}.{$user_id}";
            $cached = cache()->get($cacheKey);
            if ($cached !== null) {
                return response()->json($cached);
            }
            $assessments = Assessment::query()
                ->where('user_id', $user_id)
                ->select([
                    'id',
                    'user_id',
                    'title',
                    'course',
                    'memo',
                    'active',
                    'short_url',
                    'password',
                    'scheduled_at',
                    'created_at',
                ])
                ->with(['user:id,username'])
                ->withCount('presentations')
                ->orderByDesc('created_at')
                ->get();
            $payload = AssessmentResource::collection($assessments)->resolve();
            cache()->put($cacheKey, $payload, 2);
            return response()->json($payload);
        }

        return $this->errorResponse('forbidden', null, 403);
    }

    public function create(): View
    {
        /** @var view-string $view */
        $view = 'assessments.create';
        return view($view);
    }

    public function store(StoreAssessmentRequest $request): JsonResponse
    {
        $user = $request->user();
        $this->authorize('create', Assessment::class);

        $assessment = new Assessment([
            'title' => $request->get('title'),
            'time_limit' => $request->get('time_limit'),
            'course' => $request->get('course'),
            'penalty_method' => $request->get('penalty_method'),
            'memo' => $request->get('memo'),
            'active' => $request->get('active'),
        ]);

        $assessment->user_id = $user->id;
        $assessment->password = bin2hex(openssl_random_pseudo_bytes(4));

        $clientUrl = rtrim($request->getSchemeAndHttpHost(), '/') . '/client/' . $assessment->password;
        $preferredProvider = $request->get('shortlink_provider');
        [$shortUrl, $shortError] = $this->shortlinks->generateShortUrl($clientUrl, $preferredProvider);
        $assessment->short_url = $shortUrl;
        $assessment->bitly_error = $shortError;
        $assessment->save();

        return (new AssessmentResource($assessment))
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $id): void
    {
        //
    }

    public function edit(Assessment $assessment): JsonResponse
    {
        $cacheKey = "assessments.edit.{$assessment->id}";
        $cached = cache()->get($cacheKey);
        if ($cached) {
            return response()->json($cached);
        }

        $assessment->load([
            'questions' => function ($query) {
                $query->select(['id', 'assessment_id', 'title', 'stem', 'sequence'])
                    ->orderBy('sequence')
                    ->with(['answers' => function ($answerQuery) {
                        $answerQuery->select(['id', 'question_id', 'answer_text', 'correct', 'sequence']);
                    }]);
            },
        ]);

        $presentations = Presentation::select(['id', 'assessment_id', 'user_id', 'created_at', 'updated_at'])
            ->where('assessment_id', $assessment->id)
            ->orderBy('id')
            ->get()
            ->map(function ($presentation) {
                // UI expects a group label even if it's just the raw user_id.
                $presentation->group_label = $presentation->user_id;

                return $presentation;
            });

        $assessment->setRelation('presentations', $presentations);

        $request = request();
        $clientUrl = rtrim($request->getSchemeAndHttpHost(), '/') . '/client/' . ($assessment->password ?? '');
        $bitlyError = null;
        $preferredProvider = $request->query('shortlink_provider');
        $newShort = $assessment->short_url;
        // Lazily generate short URLs when requested by the editor.
        if (! $newShort) {
            [$newShort, $bitlyError] = $this->shortlinks->generateShortUrl($clientUrl, $preferredProvider);
        }
        if ($newShort !== $assessment->short_url) {
            $assessment->short_url = $newShort;
            $assessment->save();
        }
        $assessment->bitly_error = $bitlyError;

        $payload = (new AssessmentEditResource($assessment))->resolve();
        cache()->put($cacheKey, $payload, 2);
        return response()->json($payload);
    }

    public function update(UpdateAssessmentInlineRequest $request, Assessment $assessment): AssessmentResource
    {
        $assessment->title = $request->get('title');
        $assessment->time_limit = $request->get('time_limit') ?? null;
        $assessment->course = $request->get('course') ?? null;
        $assessment->penalty_method = $request->get('penalty_method') ?? null;
        $assessment->active = $request->get('active') ?? true;
        $scheduledAt = $request->get('scheduled_at');
        if (is_string($scheduledAt) && str_contains($scheduledAt, 'T')) {
            $pos = strpos($scheduledAt, 'T');
            if ($pos !== false) {
                $scheduledAt = substr($scheduledAt, 0, $pos);
            }
        }
        $assessment->scheduled_at = $scheduledAt;

        $assessment->memo = $request->get('memo') ?? null;
        $this->authorize('update', $assessment);

        $assessment->save();
        return new AssessmentResource($assessment);
    }

    public function destroy(Request $request, Assessment $assessment): Response
    {
        $this->authorize('delete', $assessment);

        $assessment->delete();
        return response()->noContent();
    }

    public function showPassword(int $assessment_id): View
    {
        $assessment = Assessment::select(['id', 'title', 'course', 'password'])
            ->with(['questions' => function ($query) {
                $query->select(['id', 'assessment_id', 'title', 'stem', 'sequence'])
                    ->orderBy('sequence')
                    ->with(['answers' => function ($answerQuery) {
                        $answerQuery->select(['id', 'question_id', 'answer_text', 'correct', 'sequence'])
                            ->orderBy('sequence');
                    }]);
            }])
            ->where('id', $assessment_id)
            ->get();

        /** @var view-string $view */
        $view = 'assessments.showPassword';
        return view($view, compact('assessment'));
    }
}
