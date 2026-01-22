<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAttemptRequest extends FormRequest
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
        $routeAttempt = $this->route('attempt');
        $attemptId = $routeAttempt instanceof \App\Models\Attempt
            ? $routeAttempt->id
            : (is_numeric($routeAttempt) ? (int) $routeAttempt : 0);

        return [
            'id' => [
                'required',
                'integer',
                'exists:attempts,id',
                Rule::in([$attemptId]),
            ],
            'presentation_id' => 'required|integer|exists:presentations,id',
            'answer_id' => 'required|integer|exists:answers,id',
            'points' => 'nullable|numeric',
        ];
    }
}
