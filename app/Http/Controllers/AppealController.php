<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAppealRequest;
use App\Http\Resources\AppealResource;
use App\Models\Appeal;
use App\Models\Assessment;
use App\Models\Attempt;
use App\Models\Presentation;
use App\Models\Question;

class AppealController extends Controller
{
    public function store(StoreAppealRequest $request): \Illuminate\Http\JsonResponse
    {
        $presentationId = (int) $request->input('presentation_id');
        $questionId = (int) $request->input('question_id');
        $body = trim((string) $request->input('body'));

        $presentation = Presentation::select(['id', 'assessment_id'])
            ->findOrFail($presentationId);

        $question = Question::select(['id', 'assessment_id'])
            ->findOrFail($questionId);

        if ($question->assessment_id !== $presentation->assessment_id) {
            return $this->errorResponse('forbidden', 'Question does not belong to this assessment', 403);
        }

        $assessment = Assessment::select(['id', 'appeals_open'])
            ->withCount('questions')
            ->findOrFail($presentation->assessment_id);

        if (! $assessment->appeals_open) {
            return $this->errorResponse('locked', 'Appeals submissions are closed', 423);
        }

        $correctCount = Attempt::query()
            ->join('answers', 'attempts.answer_id', '=', 'answers.id')
            ->where('attempts.presentation_id', $presentationId)
            ->where('answers.correct', true)
            ->distinct('answers.question_id')
            ->count('answers.question_id');

        if ($correctCount < (int) $assessment->questions_count) {
            return $this->errorResponse('appeals_not_ready', 'Appeals are available after all correct answers are found', 409);
        }

        $existing = Appeal::where('presentation_id', $presentationId)
            ->where('question_id', $questionId)
            ->exists();

        if ($existing) {
            return $this->errorResponse('locked', 'Appeal already submitted', 409);
        }

        $appeal = Appeal::create([
            'presentation_id' => $presentationId,
            'question_id' => $questionId,
            'body' => $body,
        ]);

        return (new AppealResource($appeal))
            ->response()
            ->setStatusCode(201);
    }
}
