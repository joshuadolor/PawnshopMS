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
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'loan_amount' => ['required', 'numeric', 'min:0'],
            'effective_interest_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'interest_rate_period' => ['required', 'in:per_annum,per_month,others'],
            'item_type' => ['required', 'string', 'max:255'],
            'item_description' => ['required', 'string'],
        ];
    }
}

