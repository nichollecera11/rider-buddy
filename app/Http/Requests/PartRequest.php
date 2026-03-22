<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PartRequest extends FormRequest
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
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');
        return [
            // Kung Update, 'sometimes' ra (pwede dili i-send ang field). 
            // Kung Store, 'required' gyud (dapat naay sulod).
            'category_id' => ($isUpdate ? 'sometimes' : 'required') . '|exists:categories,id',
            'brand_id' => ($isUpdate ? 'sometimes' : 'required') . '|exists:brands,id',
            'part_name' => ($isUpdate ? 'sometimes' : 'required') . '|string|max:255',
            'part_number' => 'nullable|string',
            'type' => ($isUpdate ? 'sometimes' : 'required') . '|in:original,replacement,aftermarket',
            'condition' => ($isUpdate ? 'sometimes' : 'required') . '|in:new,used',
            'price' => ($isUpdate ? 'sometimes' : 'required') . '|numeric|min:0',
            'is_negotiable' => ($isUpdate ? 'sometimes' : 'required') . '|boolean',
            'stock_quantity' => ($isUpdate ? 'sometimes' : 'required') . '|integer|min:0',
            'oem_compatibility' => 'nullable|string',
            'is_universal' => ($isUpdate ? 'sometimes' : 'required') . '|boolean',
            'dimensions' => 'nullable|string',
            'is_open_for_swap' => ($isUpdate ? 'sometimes' : 'required') . '|boolean',
            'swap_preferences' => 'required_if:is_open_for_swap,true,1|nullable|string',
            'description' => 'nullable|string',
            'location' => 'nullable|string',

            // 2. Image Logic para sa Store
            'images' => 'nullable|array|max:5',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',

            // 3. Image Logic para sa Update ra (Kani ra ang dugang)
            'remove_image_ids' => 'nullable|array',
            'remove_image_ids.*' => 'exists:images,id',
            'new_images' => 'nullable|array|max:5',
            'new_images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
            'primary_image_id' => 'nullable|integer|exists:images,id',
        ];
    }
}
