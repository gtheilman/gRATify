<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssessmentRequest extends FormRequest
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
            'title' => 'required|string',
            'course' => 'nullable|string',
            'memo' => 'nullable|string',
            'scheduled_at' => 'nullable|string',
            'time_limit' => 'nullable',
            'penalty_method' => 'nullable|string',
            'active' => 'nullable|boolean',
            'shortlink_provider' => 'nullable|string|in:bitly,tinyurl',
        ];
    }
}
