<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer'],
            'new_password' => ['required', 'string', 'confirmed'],
            'new_password_confirmation' => ['required', 'string'],
        ];
    }
}
