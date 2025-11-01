<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'excerpt' => $this->excerpt,
            'status' => $this->status,
            'featured_image_path' => $this->featured_image,
            'featured_image_url' => $this->featured_image ? Storage::disk('public')->url($this->featured_image) : null,
            'featured_image_alt' => $this->featured_image_alt,
            'category_slug' => $this->category_slug,
            'category' => CategoryResource::make($this->whenLoaded('category')),
            'tags' => $this->tags ?? [],
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
            'author' => UserResource::make($this->whenLoaded('author')),
            'likes_count' => $this->whenCounted('likes'),
            'comments_count' => $this->whenCounted('comments'),
        ];
    }
}
