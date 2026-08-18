<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FormSubmissionResource extends JsonResource
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
            'form_id' => $this->form_id,
            'submitted_at' => $this->submitted_at,
            'answers' => SubmissionAnswerResource::collection($this->answers()->get()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
