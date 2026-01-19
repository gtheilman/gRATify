<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAssessmentInlineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string'],
            'time_limit' => ['nullable', 'numeric'],
            'course' => ['nullable', 'string'],
            'penalty_method' => ['nullable', 'string'],
            'active' => ['nullable', 'boolean'],
            'scheduled_at' => ['nullable', 'string'],
            'memo' => ['nullable', 'string'],
        ];
    }
}
