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
            'content' => ['sometimes', 'string'],
            'status' => ['sometimes', Rule::in(['published', 'draft'])],
            'featured_image' => ['nullable', 'image', 'max:4096'],
            'excerpt' => ['sometimes', 'nullable', 'string', 'max:500'],
            'featured_image_alt' => ['sometimes', 'nullable', 'string', 'max:150'],
            'category_slug' => ['sometimes', 'nullable', 'string', 'max:100', Rule::exists('category', 'slug')],
            'tags' => ['sometimes', 'nullable', 'array'],
            'tags.*' => ['string', 'distinct', 'max:100', Rule::exists('tags', 'slug')],
        ];
    }
}
