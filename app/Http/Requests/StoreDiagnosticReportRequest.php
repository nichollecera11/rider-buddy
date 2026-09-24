<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDiagnosticReportRequest extends FormRequest
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
    public function rules(): array
    {
        return [
            'findings' => 'required|string',
            'recommended_repairs' => 'nullable|string',
            'severity' => 'required|in:minor,moderate,urgent',
            'status' => 'required|in:draft,issued',
        ];
    }
}
