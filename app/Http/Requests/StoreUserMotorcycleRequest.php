<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserMotorcycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'brand_id' => 'required|exists:brands,id',
            'model' => 'required|string|max:255',
            'year_model' => 'nullable|string',
            'plate_number' => 'required|string|unique:user_motorcycles,plate_number',
            'engine_number' => 'nullable|string|unique:user_motorcycles,engine_number',
            'chassis_number' => 'nullable|string|unique:user_motorcycles,chassis_number',
            'engine_capacity' => 'nullable|integer',
            'transmission' => 'nullable|in:manual,automatic,semi_automatic,none_electric',
            'fuel_type' => 'nullable|in:gasoline,electric',
            'color' => 'nullable|string',
            'last_registration_date' => 'nullable|date',
            'insurance_expiry' => 'nullable|date',
            'current_odometer' => 'nullable|integer',
            'is_main' => 'boolean',
            'is_active' => 'boolean'
        ];
    }
}