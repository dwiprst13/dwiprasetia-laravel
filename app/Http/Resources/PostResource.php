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
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'status' => $this->status,
            'reading_time' => $this->reading_time,
            'featured_image_path' => $this->featured_image,
            'featured_image_url' => $this->featured_image ? Storage::disk('public')->url($this->featured_image) : null,
            'thumbnail_path' => $this->thumbnail,
            'thumbnail_url' => $this->thumbnail ? Storage::disk('public')->url($this->thumbnail) : null,
            'og_image_path' => $this->og_image,
            'og_image_url' => $this->og_image ? Storage::disk('public')->url($this->og_image) : null,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'canonical_url' => $this->canonical_url,
            'published_at' => optional($this->published_at)?->toIso8601String(),
            'scheduled_at' => optional($this->scheduled_at)?->toIso8601String(),
            'view_count' => $this->view_count,
            'like_count' => $this->like_count,
            'comment_count' => $this->comment_count,
            'likes_count' => $this->like_count,
            'comments_count' => $this->comment_count,
            'allow_comments' => $this->allow_comments,
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
            'author' => UserResource::make($this->whenLoaded('author')),
            'category' => CategoryResource::make($this->whenLoaded('category')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
        ];
    }
}
