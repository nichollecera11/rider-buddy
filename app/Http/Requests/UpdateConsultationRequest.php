<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Leave true here — we'll do the "is this YOUR job" check via Policy, not here.
        // (Explained in Step 2 below.)
        return true;
    }

    public function rules(): array
    {
        return [
            'issue_description' => 'sometimes|string|min:10',
            'consultation_type' => 'sometimes|in:standard,sos',
            'status' => 'sometimes|in:pending,accepted,ongoing,completed,cancelled',
            'payment_status' => 'sometimes|in:pending,paid,failed',
            'mechanic_notes' => 'sometimes|nullable|string',
            'suggested_parts' => 'sometimes|nullable|array',
            'estimated_repair_costs' => 'sometimes|nullable|numeric',
            'verification_otp_input' => 'sometimes|string|size:6',
            'latitude' => 'sometimes|nullable|numeric',
            'longitude' => 'sometimes|nullable|numeric',
            'location_name' => 'sometimes|nullable|string',
            'images' => 'sometimes|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'videos' => 'sometimes|array',
            'videos.*' => 'mimes:mp4,mov,avi|max:20480',
        ];
    }
}