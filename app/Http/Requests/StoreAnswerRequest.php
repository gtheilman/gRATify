<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'answer_text' => 'required|string',
            'question_id' => 'required|integer|exists:questions,id',
            'sequence' => 'nullable|integer',
            'correct' => 'nullable|boolean',
            'feedback' => 'nullable|string',
        ];
    }
}
