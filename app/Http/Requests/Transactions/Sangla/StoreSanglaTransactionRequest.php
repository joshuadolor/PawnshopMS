<?php

namespace App\Http\Requests\Transactions\Sangla;

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
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string'],
            'appraised_value' => ['required', 'numeric', 'min:0'],
            'loan_amount' => [
                'required',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    // Skip decimal place validation for additional items (values come from parent transaction)
                    if (!$this->isAdditionalItem()) {
                        if (!preg_match('/^\d+(\.\d{1})?$/', (string) $value)) {
                            $fail('The '.$attribute.' may have at most 1 decimal place.');
                        }
                    }
                },
            ],
            'interest_rate' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
                function ($attribute, $value, $fail) {
                    // Skip decimal place validation for additional items (values come from parent transaction)
                    if (!$this->isAdditionalItem()) {
                        if (!preg_match('/^\d+(\.\d{1})?$/', (string) $value)) {
                            $fail('The '.$attribute.' may have at most 1 decimal place.');
                        }
                    }
                }
            ],
            'interest_rate_period' => ['required', 'in:per_annum,per_month,others'],
            'maturity_date' => ['required', 'date', 'after_or_equal:today'],
            'expiry_date' => ['required', 'date', 'after_or_equal:maturity_date'],
            'pawn_ticket_number' => [
                'required',
                'string',
                'max:100',
                function ($attribute, $value, $fail) {
                    // Only validate for new transactions, not additional items
                    if (!$this->isAdditionalItem()) {
                        $exists = \App\Models\Transaction::where('pawn_ticket_number', $value)
                            ->where('type', 'sangla')
                            ->exists();
                        
                        if ($exists) {
                            $fail('This pawn ticket number already exists. Please use the "Additional Item" button to add another item to this pawn ticket.');
                        }
                    }
                },
            ],
            'pawn_ticket_image' => [
                function ($attribute, $value, $fail) {
                    // Only required for first item, not for additional items
                    if (!$this->isAdditionalItem() && !$value) {
                        $fail('The pawn ticket image is required.');
                    }
                    if ($value && $value->getSize() > 5 * 1024 * 1024) {
                        $fail('The pawn ticket image must not be larger than 5MB.');
                    }
                },
                function ($attribute, $value, $fail) {
                    // Only validate image type and mimes if a file is provided
                    if ($value && !in_array($value->getMimeType(), ['image/jpeg', 'image/jpg', 'image/png'])) {
                        $fail('The pawn ticket image must be a JPEG or PNG image.');
                    }
                },
            ],
            'auction_sale_date' => ['nullable', 'date', 'after_or_equal:expiry_date'],
            'item_type' => ['required', 'exists:item_types,id'],
            'item_description' => ['required', 'string'],
            'item_image' => [
                'required',
                'image',
                'mimes:jpeg,jpg,png',
                'max:5120', // 5MB max
                function ($attribute, $value, $fail) {
                    if ($value && $value->getSize() > 5 * 1024 * 1024) {
                        $fail('The item image must not be larger than 5MB.');
                    }
                },
            ],
            'pawner_id_image' => [
                function ($attribute, $value, $fail) {
                    // Only required for first item, not for additional items
                    if (!$this->isAdditionalItem() && !$value) {
                        $fail('The pawner ID image is required.');
                    }
                    if ($value && $value->getSize() > 5 * 1024 * 1024) {
                        $fail('The pawner ID image must not be larger than 5MB.');
                    }
                },
            ],
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

        // If item type has subtypes, require subtype
        if ($this->itemTypeHasSubtypes()) {
            $itemTypeId = $this->input('item_type');
            $itemType = \App\Models\ItemType::with('subtypes')->find($itemTypeId);
            if ($itemType && $itemType->subtypes->count() > 0) {
                $subtypeIds = $itemType->subtypes->pluck('id')->toArray();
                $rules['item_type_subtype'] = [
                    'required',
                    'exists:item_type_subtypes,id',
                    function ($attribute, $value, $fail) use ($subtypeIds) {
                        if (!in_array($value, $subtypeIds)) {
                            $fail('The selected subtype is invalid for this item type.');
                        }
                    }
                ];
            }
        }

        // If item type has tags, require at least one tag
        if ($this->itemTypeHasTags()) {
            $itemTypeId = $this->input('item_type');
            $itemType = \App\Models\ItemType::with('tags')->find($itemTypeId);
            if ($itemType && $itemType->tags->count() > 0) {
                $tagIds = $itemType->tags->pluck('id')->toArray();
                $rules['item_type_tags'] = [
                    'required',
                    'array',
                    'min:1',
                    function ($attribute, $value, $fail) use ($tagIds) {
                        foreach ($value as $tagId) {
                            $tagIdInt = (int) $tagId;
                            if (!in_array($tagIdInt, $tagIds)) {
                                $fail('One or more selected tags are invalid for this item type.');
                                return;
                            }
                        }
                    }
                ];
                $rules['item_type_tags.*'] = ['exists:item_type_tags,id'];
            }
        }

        // If "Jewelry" is selected, require grams
        if ($this->isJewelryItemType()) {
            $rules['grams'] = [
                'required',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    // Check if value has more than one decimal place
                    if ($value !== null && $value !== '') {
                        $parts = explode('.', (string)$value);
                        if (count($parts) > 1 && strlen($parts[1]) > 1) {
                            $fail('Grams must have only one decimal place.');
                        }
                    }
                }
            ];
        }

        // If "Vehicles" or "Cars" is selected, require OR&CR/Serial
        if ($this->isVehiclesItemType()) {
            $rules['orcr_serial'] = ['required', 'string', 'max:255'];
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

    /**
     * Check if the selected item type has subtypes.
     */
    private function itemTypeHasSubtypes(): bool
    {
        $itemTypeId = $this->input('item_type');
        if (!$itemTypeId) {
            return false;
        }

        $itemType = \App\Models\ItemType::with('subtypes')->find($itemTypeId);
        
        return $itemType && $itemType->subtypes->count() > 0;
    }

    /**
     * Check if the selected item type has tags.
     */
    private function itemTypeHasTags(): bool
    {
        $itemTypeId = $this->input('item_type');
        if (!$itemTypeId) {
            return false;
        }

        $itemType = \App\Models\ItemType::with('tags')->find($itemTypeId);
        
        return $itemType && $itemType->tags->count() > 0;
    }

    /**
     * Check if "Jewelry" item type is selected.
     */
    private function isJewelryItemType(): bool
    {
        $itemTypeId = $this->input('item_type');
        if (!$itemTypeId) {
            return false;
        }

        $jewelryItemType = \App\Models\ItemType::where('name', 'Jewelry')->first();
        
        return $jewelryItemType && $itemTypeId == $jewelryItemType->id;
    }

    /**
     * Check if "Vehicles" or "Cars" item type is selected.
     */
    private function isVehiclesItemType(): bool
    {
        $itemTypeId = $this->input('item_type');
        if (!$itemTypeId) {
            return false;
        }

        $itemType = \App\Models\ItemType::find($itemTypeId);
        
        return $itemType && in_array($itemType->name, ['Vehicles', 'Cars']);
    }

    /**
     * Check if this is an additional item request.
     */
    private function isAdditionalItem(): bool
    {
        return $this->route()->getName() === 'transactions.sangla.store-additional-item';
    }
}

