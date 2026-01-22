<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuestionRequest extends FormRequest
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
        $routeQuestion = $this->route('question');
        $questionId = $routeQuestion instanceof \App\Models\Question
            ? $routeQuestion->id
            : (is_numeric($routeQuestion) ? (int) $routeQuestion : 0);

        return [
            'id' => [
                'required',
                'integer',
                'exists:questions,id',
                Rule::in([$questionId]),
            ],
            'title' => 'required|string',
            'stem' => 'required|string',
            'sequence' => 'nullable|integer',
        ];
    }
}
