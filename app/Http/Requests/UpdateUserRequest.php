<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
        $routeUser = $this->route('user') ?? $this->route('user_id');
        $userId = $routeUser instanceof \App\Models\User
            ? $routeUser->id
            : (is_numeric($routeUser) ? (int) $routeUser : 0);

        return [
            'role' => ['nullable', 'string'],
            'name' => ['nullable', 'string'],
            'username' => ['nullable', 'string'],
            'email' => ['nullable', 'email', 'unique:users,email,' . $userId],
            'company' => ['nullable', 'string'],
        ];
    }
}
