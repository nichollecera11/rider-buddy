<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMechanicRequest extends FormRequest
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
            'shop_name' => 'nullable|string|max:255',
            'address' => 'required|string',
            'bio' => 'nullable|string',
            'specialization' => 'nullable|string',
            'contact_number' => 'required|string|min:11|max:255',
            'emergency_contact' => 'nullable|string',
            'years_experience' => 'required|integer|min:0|max:50',
            'is_available' => 'boolean',
            'diagnostic_fee_base' => 'required|numeric|min:0',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'service_fee_starts_at' => 'nullable|numeric|min:0',
            'is_24_7' => 'boolean',
            'offers_towing' => 'boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ];
    }
}
