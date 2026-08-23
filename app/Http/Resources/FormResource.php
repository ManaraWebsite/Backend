<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FormResource extends JsonResource
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
            'title' => [
                'ar' => $this->title_ar,
                'en' => $this->title_en,
            ],
            'description' => [
                'ar' => $this->description_ar,
                'en' => $this->description_en,
            ],
            'slug' => $this->slug,
            'is_active' => $this->is_active,
            'translation_status' => $this->translation_status,
            'fields' => FormFieldResource::collection($this->whenLoaded('fields')),
        ];
    }
}
