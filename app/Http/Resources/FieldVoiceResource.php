<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FieldVoiceResource extends JsonResource
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
            'name' => $this->name,
            'role' => [
                'ar' => $this->role_ar,
                'en' => $this->role_en,
            ],
            'quote' => [
                'ar' => $this->quote_ar,
                'en' => $this->quote_en,
            ],
            'image' => $this->image,
            'created_at' => $this->created_at,
        ];

        if ($request->user()?->role === 'admin') {
            return [
                ...$base,
                'is_published' => $this->is_published,
                'translation_status' => $this->translation_status,
                'updated_at' => $this->updated_at,
            ];
        }

        return $base;
    }
}
