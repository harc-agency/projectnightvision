<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSymbolRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'symbol_key' => 'required|string|unique:symbols',
            'title' => 'required|string',
            'description' => 'required|string',
            'symbol' => 'required|string',
            'interpretation' => 'required|string',
            'featured_image' => 'nullable|string',
        ];
    }
}