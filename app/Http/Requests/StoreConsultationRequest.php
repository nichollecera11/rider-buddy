<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConsultationRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'mechanic_id' => 'required|exists:mechanics,id',
            'user_motorcycle_id' => 'required|exists:user_motorcycles,id',
            'issue_description' => 'required|string|min:10',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'location_name' => 'nullable|string',
            'consultation_type' => 'required|in:standard,sos',
            'images' => 'sometimes|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'videos' => 'sometimes|array',
            'videos.*' => 'mimes:mp4,mov,avi|max:20480',
        ];
    }
}