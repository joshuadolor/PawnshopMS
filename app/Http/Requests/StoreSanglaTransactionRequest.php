<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSanglaTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();
        
        $rules = [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string'],
            'appraised_value' => ['required', 'numeric', 'min:0'],
            'loan_amount' => ['required', 'numeric', 'min:0'],
            'interest_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'interest_rate_period' => ['required', 'in:per_annum,per_month,others'],
            'maturity_date' => ['required', 'date', 'after_or_equal:today'],
            'expiry_date' => ['required', 'date', 'after_or_equal:maturity_date'],
            'item_type' => ['required', 'exists:item_types,id'],
            'item_description' => ['required', 'string'],
        ];

        // Admins and superadmins can select any branch
        if ($user->isAdminOrSuperAdmin()) {
            $allBranchIds = \App\Models\Branch::pluck('id')->toArray();
            $rules['branch_id'] = ['required', 'exists:branches,id', function ($attribute, $value, $fail) use ($allBranchIds) {
                if (!in_array($value, $allBranchIds)) {
                    $fail('The selected branch is invalid.');
                }
            }];
        } else {
            // Staff users can only select their assigned branches
            $userBranchIds = $user->branches()->pluck('branches.id')->toArray();
            
            // Validate branch_id if user has multiple branches
            if (count($userBranchIds) > 1) {
                $rules['branch_id'] = ['required', 'exists:branches,id', function ($attribute, $value, $fail) use ($userBranchIds) {
                    if (!in_array($value, $userBranchIds)) {
                        $fail('The selected branch is invalid.');
                    }
                }];
            } elseif (count($userBranchIds) === 1) {
                // If user has only one branch, ensure it matches
                $rules['branch_id'] = ['required', 'exists:branches,id', function ($attribute, $value, $fail) use ($userBranchIds) {
                    if ($value != $userBranchIds[0]) {
                        $fail('The selected branch is invalid.');
                    }
                }];
            }
        }

        // If "Other" is selected, require custom_item_type
        if ($this->isOtherItemType()) {
            $rules['custom_item_type'] = ['required', 'string', 'min:3', 'max:255'];
        }

        return $rules;
    }

    /**
     * Check if "Other" item type is selected.
     */
    private function isOtherItemType(): bool
    {
        $itemTypeId = $this->input('item_type');
        if (!$itemTypeId) {
            return false;
        }

        $otherItemType = \App\Models\ItemType::where('name', 'Other')->first();
        
        return $otherItemType && $itemTypeId == $otherItemType->id;
    }
}

