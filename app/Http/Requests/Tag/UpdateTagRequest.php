<?php

namespace App\Http\Requests\Tag;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTagRequest extends FormRequest
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
        $tagId = $this->route('tag')?->id;

        return [
            'name' => ['sometimes', 'string', 'max:100', Rule::unique('tags', 'name')->ignore($tagId)],
            'slug' => ['sometimes', 'nullable', 'string', 'max:100', Rule::unique('tags', 'slug')->ignore($tagId)],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
