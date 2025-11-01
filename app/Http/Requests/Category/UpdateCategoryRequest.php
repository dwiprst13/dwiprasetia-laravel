<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $categoryId = $this->route('category')?->id;

        return [
            'name' => ['sometimes', 'string', 'max:100', Rule::unique('category', 'name')->ignore($categoryId)],
            'slug' => ['sometimes', 'nullable', 'string', 'max:100', Rule::unique('category', 'slug')->ignore($categoryId)],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
