<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string',
            'stem' => 'required|string',
            'assessment_id' => 'required|integer|exists:assessments,id',
            'sequence' => 'nullable|integer',
        ];
    }
}
