<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $routeAnswer = $this->route('answer');
        $answerId = $routeAnswer instanceof \App\Models\Answer ? $routeAnswer->id : $routeAnswer;

        return [
            'id' => [
                'required',
                'integer',
                'exists:answers,id',
                Rule::in([(int) $answerId]),
            ],
            'answer_text' => 'required|string',
            'sequence' => 'nullable|integer',
            'correct' => 'nullable|boolean',
            'feedback' => 'nullable|string',
        ];
    }
}
