<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->route('user');
        
        // Prevent admin from modifying superadmin
        if ($this->user()->isAdmin() && $user instanceof User && $user->isSuperAdmin()) {
            return false;
        }

        // Prevent changing superadmin role
        if ($user instanceof User && $user->isSuperAdmin()) {
            return false;
        }

        return $this->user()->isAdminOrSuperAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'role' => ['required', 'string', 'in:admin,staff'],
        ];
    }
}

