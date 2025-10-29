<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentReportResource extends JsonResource
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
            'comment_id' => $this->comment_id,
            'status' => $this->status,
            'reason' => $this->reason,
            'handled_by' => $this->handled_by,
            'handled_at' => optional($this->handled_at)?->toIso8601String(),
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
            'comment' => CommentResource::make($this->whenLoaded('comment')),
            'reporter' => UserResource::make($this->whenLoaded('reporter')),
            'handler' => UserResource::make($this->whenLoaded('handler')),
        ];
    }
}
