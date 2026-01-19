<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'presentation_id' => 'required|integer|exists:presentations,id',
            'answer_id' => 'required|integer|exists:answers,id',
            'points' => 'nullable|numeric',
        ];
    }
}
