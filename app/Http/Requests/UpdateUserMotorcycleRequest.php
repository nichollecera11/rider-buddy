<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserMotorcycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userMotorcycleId = $this->route('user_motorcycle')->id;

        return [
            'brand_id' => 'required|exists:brands,id',
            'model' => 'required|string|max:255',
            'year_model' => 'nullable|digits:4',
            'plate_number' => 'required|string|unique:user_motorcycles,plate_number,' . $userMotorcycleId,
            'engine_number' => 'nullable|string|unique:user_motorcycles,engine_number,' . $userMotorcycleId,
            'chassis_number' => 'nullable|string|unique:user_motorcycles,chassis_number,' . $userMotorcycleId,
            'transmission' => 'nullable|in:manual,automatic,semi_automatic,none_electric',
            'fuel_type' => 'nullable|in:gasoline,electric',
            'color' => 'nullable|string',
            'last_registration_date' => 'nullable|date',
            'insurance_expiry' => 'nullable|date',
            'current_odometer' => 'nullable|integer',
            'is_main' => 'boolean',
            'is_active' => 'boolean',
            'verification_photo' => 'required_with:current_odometer,last_registration_date|image|max:2048',
        ];
    }
}