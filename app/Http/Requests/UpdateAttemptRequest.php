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

    public function rules(): array
    {
        $routeAttempt = $this->route('attempt');
        $attemptId = $routeAttempt instanceof \App\Models\Attempt ? $routeAttempt->id : $routeAttempt;

        return [
            'id' => [
                'required',
                'integer',
                'exists:attempts,id',
                Rule::in([(int) $attemptId]),
            ],
            'presentation_id' => 'required|integer|exists:presentations,id',
            'answer_id' => 'required|integer|exists:answers,id',
            'points' => 'nullable|numeric',
        ];
    }
}
