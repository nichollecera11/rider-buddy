<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMaintenanceLogRequest extends FormRequest
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
            'service_type' => 'sometimes|required|string',
            'description' => 'sometimes|nullable|string',
            'odometer_reading' => 'sometimes|required|integer|min:0',
            'cost' => 'sometimes|nullable|integer|min:0',
        ];
    }
}
