<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLTOComplianceRequest extends FormRequest
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
            // Kinahanglan unique ni sila sa l_t_o_compliances table para anti-fraud
            'plate_number' => 'sometimes|required|string|unique:l_t_o_compliances,plate_number',
            'engine_number' => 'sometimes|required|string|unique:l_t_o_compliances,engine_number',
            'chassis_number' => 'sometimes|required|string|unique:l_t_o_compliances,chassis_number',
            'registration_expiry' => 'sometimes|required|date|after:today',

            // Ang OR/CR photo/file
            'file' => 'sometimes|required|image|mimes:jpeg,png,jpg|max:2048',
        ];
    }
}
