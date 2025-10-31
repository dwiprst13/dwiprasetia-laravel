<?php

namespace App\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePostRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:posts,slug'],
            'excerpt' => ['nullable', 'string'],
            'content' => ['required', 'string'],
            'reading_time' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'status' => ['nullable', Rule::in(['draft', 'scheduled', 'published', 'archived'])],
            'featured_image' => ['nullable', 'image', 'max:4096'],
            'thumbnail' => ['nullable', 'image', 'max:4096'],
            'og_image' => ['nullable', 'image', 'max:4096'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'canonical_url' => ['nullable', 'url', 'max:255'],
            'published_at' => ['nullable', 'date'],
            'scheduled_at' => ['nullable', 'date'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],
            'allow_comments' => ['sometimes', 'boolean'],
        ];
    }
}
