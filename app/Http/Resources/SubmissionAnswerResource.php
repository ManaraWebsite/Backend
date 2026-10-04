<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubmissionAnswerResource extends JsonResource
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
            'submission_id' => $this->submission_id,
            'field_id' => $this->field_id,
            'field-name' => $this->field->label_ar,
            'field_type' => $this->field->type,
            // File answers are a private path; expose a short-lived link instead.
            'answer' => $this->field->type === 'file'
                ? $this->fileUrl(now()->addHour())
                : $this->answer,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
