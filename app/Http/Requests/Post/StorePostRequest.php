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
            'content' => ['required', 'string'],
            'status' => ['required', Rule::in(['published', 'draft'])],
            'featured_image' => ['nullable', 'image', 'max:4096'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'featured_image_alt' => ['nullable', 'string', 'max:150'],
            'category_slug' => ['nullable', 'string', 'max:100', Rule::exists('category', 'slug')],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'distinct', 'max:100', Rule::exists('tags', 'slug')],
        ];
    }
}
