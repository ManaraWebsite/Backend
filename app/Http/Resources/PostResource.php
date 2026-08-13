<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($request->user() && $request->user()->role === 'admin') {
            return [
                'id' => $this->id,
                'title' => $this->title,
                'content' => $this->content,
                'slug' => $this->slug,
                'cover_image' => $this->cover_image,
                'status' => $this->status,
                'published_at' => $this->published_at,
                'author' => new UserResource($this->whenLoaded('author')),
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ];
        } else {
            return [
                'id' => $this->id,
                'title' => $this->title,
                'content' => $this->content,
                'slug' => $this->slug,
                'cover_image' => $this->cover_image,
                'status' => $this->status,
                'published_at' => $this->published_at,
            ];
        }
    }
}
