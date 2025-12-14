<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBranchFinancialTransactionRequest extends FormRequest
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
            'branch_id' => ['required', 'exists:branches,id'],
            'type' => ['required', 'in:expense,replenish'],
            'description' => ['required', 'string', 'min:3', 'max:500'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'transaction_date' => ['required', 'date', 'before_or_equal:today'],
        ];
    }
}
