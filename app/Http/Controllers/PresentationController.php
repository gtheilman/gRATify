<?php

namespace App\Http\Controllers;

use App\Http\Resources\PublicAssessmentResource;
use App\Http\Resources\PublicPresentationResource;
use App\Http\Resources\PresentationListResource;
use App\Http\Resources\ScoredPresentationResource;
use App\Models\Assessment;
use App\Models\Presentation;
use App\Services\Scoring\PresentationScorer;
use App\Services\Presentations\PresentationAssembler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Handles public presentation flows and staff scoring views.
 */
class PresentationController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct(
        private PresentationScorer $presentationScorer,
        private PresentationAssembler $presentationAssembler
    )
    {
        $this->middleware('auth:web', ['except' => ['store', 'show', 'getAssessment', 'scoreByCredentials']]);
    }


    /**
     * Display a listing of the resource.
     *
     * @return void
     */
    public function index(): void
    {

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
     * @param string $password
     * @param string $user_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(string $password, string $user_id): JsonResponse
    {

        // Public endpoint: locate assessment by password and prevent access if inactive.
        $assessment = Assessment::with('questions.answers')->firstWhere('password', $password);
        if (! $assessment) {
            return $this->errorResponse('not_found', null, 404);
        }
        if (!$assessment->active) {
            return $this->errorResponse('forbidden', null, 403);
        }
        $result = $this->presentationAssembler->assemblePublic($assessment, $user_id);
        $presentation = $result['presentation'];
        $presentationCreated = $result['created'];

        return (new PublicPresentationResource($presentation))
            ->response()
            ->setStatusCode($presentationCreated ? 201 : 200);
    }


    /**
     * Score a presentation.
     *
     * @param \App\Http\Requests\ScoreByCredentialsRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function scoreByCredentials(\App\Http\Requests\ScoreByCredentialsRequest $request): JsonResponse
    {
        $password = $request->route('password');
        $user_id = $request->route('user_id');

        $assessment = Assessment::with(['questions' => function ($query) {
            $query->withCount('answers');
        }])->firstWhere('password', $password);
        if (! $assessment) {
            return $this->errorResponse('not_found', null, 404);
        }
        $presentation = Presentation::where('assessment_id', $assessment->id)
            ->where('user_id', $user_id)
            ->first();

        if (!$presentation) {
            return $this->errorResponse('not_found', null, 404);
        }

        $presentation->load('attempts.answer');

        $scoredPresentation = $this->buildScoredPresentation($presentation, $assessment);

        return response()->json($scoredPresentation->score);
    }

    /**
     * Score   presentations by assessment_id
     *
     * @param \Illuminate\Http\Request $request
     * @param int $assessment_id
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
     */
    public function scoreByAssessmentId(Request $request, int $assessment_id): JsonResponse|AnonymousResourceCollection
    {

        $assessment = Assessment::with(['questions' => function ($query) {
            $query->orderBy('sequence')
                ->withCount('answers');
        }])->findOrFail($assessment_id);
        $this->authorize('viewForAssessment', [Presentation::class, $assessment]);

        $scheme = $request->query('scheme', config('scoring.default', 'geometric-decay'));
        $scheme = is_string($scheme) ? $scheme : (string) $scheme;
        $allowedSchemes = array_keys(config('scoring.schemes', []));
        if (! in_array($scheme, $allowedSchemes, true)) {
            return $this->errorResponse('invalid_scheme', null, 422);
        }

        $presentations = Presentation::with(['attempts.answer'])
            ->where('assessment_id', $assessment_id)
            ->get();

        $scoredPresentations = $presentations->map(function ($presentation) use ($assessment, $scheme) {
            // Clone the assessment/questions to avoid cross-contamination between presentations.
            $assessmentClone = $assessment->replicate();
            $questionClones = $assessment->questions->map(function ($question) {
                $clone = $question->replicate();
                $clone->id = $question->id;
                return $clone;
            });
            $assessmentClone->setRelation('questions', $questionClones);
            return $this->presentationScorer->score($presentation, $assessmentClone, $scheme);
        });

        return ScoredPresentationResource::collection($scoredPresentations);
    }


    /**
     * Build a scored presentation with question attempts attached.
     */
    private function buildScoredPresentation(Presentation $presentation, Assessment $assessment, ?string $scheme = null): Presentation
    {
        return $this->presentationScorer->score($presentation, $assessment, $scheme);
    }

    /**
     * Display the specified resource.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        $assessment = Assessment::with('questions.answers')->where('password', $id)->firstOrFail();
        $assessment->setRelation('questions', $assessment->questions->sortBy(function ($question) {
            return $question->sequence;
        }));


        return response()->json($assessment);
    }


    /**
     * Get the assessment questions
     *
     * @param int $presentation_id
     * @return \App\Http\Resources\PublicAssessmentResource|\Illuminate\Http\JsonResponse
     */
    public function getAssessment(int $presentation_id): PublicAssessmentResource|JsonResponse
    {
        $presentation = Presentation::find($presentation_id);
        if (! $presentation) {
            return $this->errorResponse('not_found', null, 404);
        }
        $assessment_id = $presentation->assessment_id;

        $assessment = Assessment::with('questions.answers')->find($assessment_id);
        $presentation->setRelation('assessment', $assessment);

        return new PublicAssessmentResource($assessment);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return void
     */
    public function edit(int $id): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return void
     */
    public function update(Request $request, int $id): void
    {
        //
    }

    /**
     * List asssements that have been taken
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function completed(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Presentation::class);

        $presentations = Presentation::query()
            ->with(['assessment.user'])
            ->whereHas('assessment', function ($query) {
                $query->where('user_id', '!=', config('grat.admin_id'));
            })
            ->get();

        return PresentationListResource::collection($presentations);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return void
     */
    public function destroy(int $id): void
    {
        //
    }
}
