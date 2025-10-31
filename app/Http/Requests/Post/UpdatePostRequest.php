<?php

namespace App\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePostRequest extends FormRequest
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
            'title' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('posts', 'slug')->ignore($this->route('post')?->id),
            ],
            'excerpt' => ['sometimes', 'nullable', 'string'],
            'content' => ['sometimes', 'string'],
            'reading_time' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:1440'],
            'status' => ['sometimes', Rule::in(['draft', 'scheduled', 'published', 'archived'])],
            'featured_image' => ['sometimes', 'nullable', 'image', 'max:4096'],
            'thumbnail' => ['sometimes', 'nullable', 'image', 'max:4096'],
            'og_image' => ['sometimes', 'nullable', 'image', 'max:4096'],
            'meta_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'meta_description' => ['sometimes', 'nullable', 'string'],
            'canonical_url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'published_at' => ['sometimes', 'nullable', 'date'],
            'scheduled_at' => ['sometimes', 'nullable', 'date'],
            'category_id' => ['sometimes', 'nullable', 'exists:categories,id'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],
            'allow_comments' => ['sometimes', 'boolean'],
        ];
    }
}
