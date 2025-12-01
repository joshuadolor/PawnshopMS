<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateConfigRequest extends FormRequest
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
        $rules = [];

        // Get all configs to validate based on their type
        $configs = \App\Models\Config::all();

        foreach ($configs as $config) {
            $key = "configs.{$config->key}";

            switch ($config->type) {
                case 'number':
                case 'decimal':
                    $rules[$key] = ['nullable', 'numeric', 'min:0'];
                    break;
                case 'percentage':
                    $rules[$key] = ['nullable', 'numeric', 'min:0', 'max:100'];
                    break;
                default:
                    $rules[$key] = ['nullable', 'string', 'max:255'];
                    break;
            }
        }

        return $rules;
    }
}

