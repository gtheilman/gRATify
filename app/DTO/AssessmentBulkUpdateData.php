<?php

namespace App\DTO;

/**
 * Normalizes validated bulk payloads so controller/service logic stays simple.
 */
class AssessmentBulkUpdateData
{
    /**
     * @param array<string, mixed> $assessment
     * @param array<int, array<string, mixed>> $questions
     */
    public function __construct(
        public array $assessment,
        public array $questions
    ) {
    }

    /**
     * @param array<string, mixed> $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            $validated['assessment'],
            $validated['questions'] ?? []
        );
    }

    /**
     * @return array<int, int>
     */
    public function questionIds(): array
    {
        return collect($this->questions)->pluck('id')->filter()->values()->all();
    }

    /**
     * @return array<int, array{question_id:int,answer:array<string,mixed>}>
     */
    public function answerPayloads(): array
    {
        $answerPayloads = [];
        foreach ($this->questions as $questionData) {
            foreach ($questionData['answers'] ?? [] as $answerData) {
                $answerPayloads[] = [
                    'question_id' => $questionData['id'],
                    'answer' => $answerData,
                ];
            }
        }

        return $answerPayloads;
    }

    /**
     * @return array<int, int>
     */
    public function answerIds(): array
    {
        return collect($this->answerPayloads())->pluck('answer.id')->filter()->values()->all();
    }
}
