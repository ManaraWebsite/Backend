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
        $base = [
            'id' => $this->id,
            'title' => [
                'ar' => $this->title_ar,
                'en' => $this->title_en,
            ],
            'content' => [
                'ar' => $this->content_ar,
                'en' => $this->content_en,
            ],
            'slug' => $this->slug,
            'cover_image' => $this->cover_image,
            'status' => $this->status,
            'published_at' => $this->published_at,
        ];

        if ($request->user() && $request->user()->role === 'admin') {
            return [
                ...$base,
                'translation_status' => $this->translation_status,
                'author' => new UserResource($this->whenLoaded('author')),
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ];
        }

        return $base;
    }
}
