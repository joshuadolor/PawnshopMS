<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->isAdminOrSuperAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'alphanum', 'min:4', 'max:100', 'unique:'.User::class.',username'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'string', 'in:admin,staff'],
            'is_active' => ['boolean'],
        ];

        // Only validate password if provided
        if ($this->filled('password')) {
            $rules['password'] = ['required', 'confirmed', Rules\Password::defaults()];
        }

        return $rules;
    }
}

