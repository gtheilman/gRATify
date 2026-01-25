<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public answer payload (no correctness fields).
 *
 * @mixin \App\Models\Answer
 */
class PublicAnswerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $password = (string) $request->route('password', '');

        return [
            'id' => $this->id,
            'question_id' => $this->question_id,
            'answer_text' => $this->answer_text,
            'sequence' => $this->sequence,
            'correct_scrambled' => $this->scrambleCorrect((bool) $this->correct, $password),
        ];
    }

    private function scrambleCorrect(bool $value, string $key): ?string
    {
        if ($key === '') {
            return null;
        }
        $raw = $value ? '1' : '0';
        $first = $key[0] ?? "\0";
        $byte = chr(ord($raw) ^ ord($first));
        return base64_encode($byte);
    }
}
