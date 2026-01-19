<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnswerReorderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'answer_id' => ['required', 'integer', 'exists:answers,id'],
            'question_id' => ['required', 'integer', 'exists:questions,id'],
        ];
    }
}
