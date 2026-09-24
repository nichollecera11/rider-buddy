<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDiagnosticReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array {
    return [
        'findings' => 'sometimes'|'required|string',
        'recommended_repairs' => 'sometimes'|'nullable|string',
        'severity' => 'sometimes'|'required|in:minor,moderate,urgent',
        'status' => 'sometimes'|'required|in:draft,issued',
    ];
}
}
