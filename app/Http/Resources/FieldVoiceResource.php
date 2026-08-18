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
        if ($request->user()?->role === 'admin') {
            return [
                'id' => $this->id,
                'name' => $this->name,
                'role' => $this->role,
                'quote' => $this->quote,
                'image' => $this->image,
                'is_published' => $this->is_published,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ];
        } else {
            return [
                'name' => $this->name,
                'role' => $this->role,
                'quote' => $this->quote,
                'image' => $this->image,
                'created_at' => $this->created_at,
            ];
        }
    }
}
