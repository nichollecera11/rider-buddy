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
            // Consultation status transitions
            'status' => 'sometimes|in:accepted,ongoing,completed',
            // Required when mechanic starts the service
            'verification_otp_input' => 'sometimes|required_if:status,ongoing|digits:6',
            // Mechanic service updates
            'mechanic_notes' => 'sometimes|nullable|string',
            'suggested_parts' => 'sometimes|nullable|string',
            'estimated_repair_costs' => 'sometimes|nullable|numeric|min:0',
            'issue_description' => 'sometimes|string|min:10',
            'consultation_type' => 'sometimes|in:standard,sos',
            'payment_status' => 'sometimes|in:pending,paid,failed',
            'latitude' => 'sometimes|nullable|numeric',
            'longitude' => 'sometimes|nullable|numeric',
            'location_name' => 'sometimes|nullable|string',
        ];
    }
}