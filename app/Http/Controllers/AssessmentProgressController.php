<?php

namespace App\Http\Controllers;

use App\Services\Assessments\AssessmentProgressService;
use App\Http\Resources\AssessmentProgressResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

/**
 * Thin controller: delegates progress assembly to a service so it can be tested in isolation.
 */
class AssessmentProgressController extends Controller
{
    public function __construct(private AssessmentProgressService $progressService)
    {
        $this->middleware('auth:web');
    }

    /**
     * Return the attempts for a given assessment
     */
    public function attempts(int $assessment_id): JsonResponse|AssessmentProgressResource
    {
        try {
            $cacheKey = "assessment.progress.{$assessment_id}";
            $cached = cache()->get($cacheKey);
            if ($cached !== null) {
                return response()->json($cached, 200);
            }
            $assessment = $this->progressService->build($assessment_id);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('not_found', null, 404);
        }
        $payload = (new AssessmentProgressResource($assessment))->resolve();
        cache()->put($cacheKey, $payload, 2);
        return response()->json($payload, 200);
    }
}
