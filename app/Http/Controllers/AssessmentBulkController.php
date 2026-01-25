<?php

namespace App\Http\Controllers;

use App\DTO\AssessmentBulkUpdateData;
use App\Models\Assessment;
use App\Models\Answer;
use App\Models\Presentation;
use App\Models\Question;
use App\Services\Assessments\AssessmentBulkUpdater;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Accepts a full assessment payload for fast editor "save all" operations.
 */
class AssessmentBulkController extends Controller
{
    public function __construct(private AssessmentBulkUpdater $bulkUpdater)
    {
        $this->middleware('auth:web');
    }

    /**
     * Bulk update assessment, questions, and answers in one request.
     */
    public function bulkUpdate(Request $request, Assessment $assessment): JsonResponse
    {
        $this->authorize('update', $assessment);

        // Prevent edits once any learner attempts exist.
        $hasResponses = Presentation::where('assessment_id', $assessment->id)->exists();
        if ($hasResponses) {
            return $this->errorResponse('locked', null, 403);
        }

        $validated = $request->validate([
            'assessment' => ['required', 'array'],
            'assessment.title' => ['required', 'string'],
            'assessment.course' => ['nullable', 'string'],
            'assessment.memo' => ['nullable', 'string'],
            'assessment.scheduled_at' => ['nullable', 'string'],
            'assessment.time_limit' => ['nullable'],
            'assessment.penalty_method' => ['nullable', 'string'],
            'assessment.active' => ['nullable', 'boolean'],
            'questions' => ['array'],
            'questions.*.id' => ['required', 'integer'],
            'questions.*.title' => ['nullable', 'string'],
            'questions.*.stem' => ['required', 'string'],
            'questions.*.sequence' => ['required', 'integer'],
            'questions.*.answers' => ['array'],
            'questions.*.answers.*.id' => ['required', 'integer'],
            'questions.*.answers.*.answer_text' => ['required', 'string'],
            'questions.*.answers.*.correct' => ['nullable', 'boolean'],
            'questions.*.answers.*.sequence' => ['required', 'integer'],
        ]);

        // Convert to a DTO so validation mapping stays predictable.
        $dto = AssessmentBulkUpdateData::fromValidated($validated);

        // Enforce ownership of question IDs to block cross-assessment edits.
        $questionIds = collect($dto->questionIds());
        if ($questionIds->isNotEmpty()) {
        $validQuestionIds = Question::where('assessment_id', $assessment->id)
            ->whereIn('id', $questionIds)
            ->pluck('id')
            ->all();
        if (count($validQuestionIds) !== $questionIds->count()) {
            throw ValidationException::withMessages([
                'questions' => ['Question does not belong to assessment.'],
            ]);
        }
        }

        // Enforce ownership of answer IDs to block cross-question edits.
        $answerIds = collect($dto->answerIds());
        if ($answerIds->isNotEmpty()) {
        $validAnswerIds = Answer::whereIn('question_id', $questionIds)
            ->whereIn('id', $answerIds)
            ->pluck('id')
            ->all();
        if (count($validAnswerIds) !== $answerIds->count()) {
            throw ValidationException::withMessages([
                'answers' => ['Answer does not belong to question.'],
            ]);
        }
        }

        $this->bulkUpdater->update($assessment, $dto->assessment, $dto->questions);

        return response()->json(['status' => 'saved']);
    }
}
