<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConsultationLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization will be handled by ConsultationPolicy.
        return true;
    }

    public function rules(): array
    {
        return [
            'latitude' => [
                'required',
                'numeric',
                'between:-90,90',
            ],
            'longitude' => [
                'required',
                'numeric',
                'between:-180,180',
            ],
            'location_name' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }
}
