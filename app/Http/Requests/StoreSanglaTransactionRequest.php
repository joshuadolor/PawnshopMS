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
        $rules = [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string'],
            'loan_amount' => ['required', 'numeric', 'min:0'],
            'interest_rate_period' => ['required', 'in:per_annum,per_month,others'],
            'maturity_date' => ['required', 'date', 'after_or_equal:today'],
            'expiry_date' => ['required', 'date', 'after_or_equal:maturity_date'],
            'item_type' => ['required', 'exists:item_types,id'],
            'item_description' => ['required', 'string'],
        ];

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

