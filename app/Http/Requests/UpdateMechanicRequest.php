<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateMechanicRequest extends FormRequest
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
            'bio' => 'sometimes|nullable|string',
            'specialization' => 'sometimes|nullable|string',
            'contact_number' => 'sometimes|required|string|min:11|max:255',
            'emergency_contact' => 'sometimes|nullable|string',
            'years_experience' => 'sometimes|required|integer|min:0|max:50',
            'is_available' => 'sometimes|boolean',
            'diagnostic_fee_base' => 'sometimes|required|numeric|min:0',
            'latitude' => [
                'sometimes',
                'nullable',
                'required_with:longitude',
                'numeric',
                'between:-90,90',
            ],

            'longitude' => [
                'sometimes',
                'nullable',
                'required_with:latitude',
                'numeric',
                'between:-180,180',
            ],
            'service_fee_starts_at' => 'sometimes|nullable|numeric|min:0',
            'is_24_7' => 'sometimes|boolean',
            'offers_towing' => 'sometimes|boolean',
            'image' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ];
    }
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $latitudeProvided = $this->exists('latitude');
                $longitudeProvided = $this->exists('longitude');

                if ($latitudeProvided && !$longitudeProvided) {
                    $validator->errors()->add(
                        'longitude',
                        'Longitude is required when latitude is provided.'
                    );
                }

                if ($longitudeProvided && !$latitudeProvided) {
                    $validator->errors()->add(
                        'latitude',
                        'Latitude is required when longitude is provided.'
                    );
                }
            },
        ];
    }
}
