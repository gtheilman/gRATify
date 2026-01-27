<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppealRequest extends FormRequest
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
            'presentation_id' => ['required', 'integer', 'exists:presentations,id'],
            'question_id' => ['required', 'integer', 'exists:questions,id'],
            'body' => ['required', 'string'],
        ];
    }
}
