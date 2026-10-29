<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'mechanic_id' => [
                'required',
                'integer',
                Rule::exists('mechanics', 'id'),
            ],

            'user_motorcycle_id' => [
                'required',
                'integer',
                Rule::exists('user_motorcycles', 'id')
                    ->where('user_id', $this->user()?->id)
                    ->whereNull('deleted_at'),
            ],

            'consultation_type' => [
                'required',
                Rule::in(['standard', 'sos']),
            ],

            'issue_description' => [
                'required',
                'string',
                'min:10',
                'max:2000',
            ],

            'latitude' => [
                'nullable',
                'numeric',
                'between:-90,90',
            ],

            'longitude' => [
                'nullable',
                'numeric',
                'between:-180,180',
            ],

            'location_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'images' => [
                'sometimes',
                'array',
                'max:10',
            ],

            'images.*' => [
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'videos' => [
                'sometimes',
                'array',
                'max:3',
            ],

            'videos.*' => [
                'file',
                'mimes:mp4,mov,avi',
                'max:20480',
            ],
        ];
    }
}
