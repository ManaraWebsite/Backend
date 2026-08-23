<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FormFieldResource extends JsonResource
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
            'label' => [
                'ar' => $this->label_ar,
                'en' => $this->label_en,
            ],
            'type' => $this->type,
            // Internal values (used for validation/submission) stay in `ar`;
            // `en` is a display-only translation of the same choice, in order.
            'options' => $this->options === null ? null : collect($this->options)
                ->map(fn ($option, $index) => [
                    'ar' => $option,
                    'en' => $this->options_en[$index] ?? null,
                ])
                ->values(),
            'order' => $this->order,
            'is_required' => $this->is_required,
            'translation_status' => $this->translation_status,
        ];
    }
}
