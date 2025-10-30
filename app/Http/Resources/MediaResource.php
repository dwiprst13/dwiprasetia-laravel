<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class MediaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $url = null;

        if ($this->path) {
            $disk = $this->disk ?? 'public';

            try {
                $url = Storage::disk($disk)->url($this->path);
            } catch (\Throwable) {
                $url = null;
            }
        }

        return [
            'id' => $this->id,
            'disk' => $this->disk,
            'path' => $this->path,
            'url' => $url,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'extension' => $this->extension,
            'size' => $this->size,
            'alt_text' => $this->alt_text,
            'caption' => $this->caption,
            'uploaded_by' => $this->whenLoaded('uploader', fn () => UserResource::make($this->uploader)),
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
        ];
    }
}
