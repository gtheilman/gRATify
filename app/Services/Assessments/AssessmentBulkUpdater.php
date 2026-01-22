<?php

namespace App\Services\Assessments;

use App\Models\Answer;
use App\Models\Assessment;
use App\Models\Question;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Performs assessment/question/answer updates in one transaction to keep
 * bulk edits consistent and fast for the editor UI.
 */
class AssessmentBulkUpdater
{
    /**
     * @param array<string, mixed> $assessmentData
     * @param array<int, array<string, mixed>> $questionPayloads
     */
    public function update(Assessment $assessment, array $assessmentData, array $questionPayloads): void
    {
        // Wrap the full bulk update to avoid partial saves across tables.
        DB::transaction(function () use ($assessment, $assessmentData, $questionPayloads) {
            $assessment->title = $assessmentData['title'] ?? $assessment->title;
            $assessment->time_limit = $assessmentData['time_limit'] ?? null;
            $assessment->course = $assessmentData['course'] ?? null;
            $assessment->penalty_method = $assessmentData['penalty_method'] ?? null;
            $assessment->active = $assessmentData['active'] ?? true;
            $scheduledAt = $assessmentData['scheduled_at'] ?? null;
            if (is_string($scheduledAt) && str_contains($scheduledAt, 'T')) {
                $pos = strpos($scheduledAt, 'T');
                if ($pos !== false) {
                    $scheduledAt = substr($scheduledAt, 0, $pos);
                }
            }
            $assessment->scheduled_at = is_string($scheduledAt) && $scheduledAt !== ''
                ? Carbon::parse($scheduledAt)
                : null;
            $assessment->memo = $assessmentData['memo'] ?? null;
            $assessment->save();

            $answerPayloads = [];
            foreach ($questionPayloads as $questionData) {
                foreach ($questionData['answers'] ?? [] as $answerData) {
                    $answerPayloads[] = [
                        'question_id' => $questionData['id'],
                        'answer' => $answerData,
                    ];
                }
            }

            // Use upserts to avoid per-row update loops for large assessments.
            if (!empty($questionPayloads)) {
                $questionRows = collect($questionPayloads)->map(function ($questionData) use ($assessment) {
                    return [
                        'id' => $questionData['id'],
                        'assessment_id' => $assessment->id,
                        'title' => $questionData['title'] ?? $questionData['stem'],
                        'stem' => $questionData['stem'],
                        'sequence' => $questionData['sequence'],
                        'updated_at' => now(),
                    ];
                })->all();
                DB::table('questions')->upsert(
                    $questionRows,
                    ['id'],
                    ['title', 'stem', 'sequence', 'updated_at']
                );
            }

            if (!empty($answerPayloads)) {
                $answerRows = collect($answerPayloads)->map(function ($item) {
                    $answerData = $item['answer'];
                    return [
                        'id' => $answerData['id'],
                        'question_id' => $item['question_id'],
                        'answer_text' => $answerData['answer_text'],
                        'sequence' => $answerData['sequence'],
                        'correct' => (bool) ($answerData['correct'] ?? false),
                        'updated_at' => now(),
                    ];
                })->all();
                DB::table('answers')->upsert(
                    $answerRows,
                    ['id'],
                    ['answer_text', 'sequence', 'correct', 'updated_at']
                );
            }
        });
    }
}
