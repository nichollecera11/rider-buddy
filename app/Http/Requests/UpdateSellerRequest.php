<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSellerRequest extends FormRequest
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
            'shop_name' => 'sometimes|nullable|string|max:255',
            'address' => 'sometimes|required|string',
            'contact_number' => 'sometimes|required|string|min:11|max:255',
            'business_permit_no' => 'sometimes|nullable|string|max:255',
            'has_delivery' => 'sometimes|boolean',
            'description' => 'sometimes|nullable|string',
            'latitude' => 'sometimes|nullable|numeric|between:-90,90',
            'longitude' => 'sometimes|nullable|numeric|between:-180,180',
            'is_24_7' => 'sometimes|boolean',
            'image' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ];
    }
}
