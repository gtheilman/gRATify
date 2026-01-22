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
            $assessment = $this->progressService->build($assessment_id);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('not_found', null, 404);
        }

        return new AssessmentProgressResource($assessment);
    }
}
