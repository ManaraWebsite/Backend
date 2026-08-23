<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatableContent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormField extends Model
{
    use HasFactory, HasTranslatableContent;

    protected $attributes = [
        'translation_status' => 'pending',
    ];

    protected $fillable = [
        'form_id',
        'label_ar',
        'label_en',
        'type',
        'options',
        'options_en',
        'is_required',
        'order',
        'translation_status',
    ];

    protected $casts = [
        'options' => 'array',
        'options_en' => 'array',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    public function answers()
    {
        return $this->hasMany(SubmissionAnswer::class);
    }

    protected function translatableFields(): array
    {
        return ['label'];
    }

    protected function hasAdditionalDirtyTranslatableContent(): bool
    {
        return $this->wasChanged('options');
    }

    /**
     * Include the Arabic option choices (option_0, option_1, ...) alongside
     * the label so the whole field is translated in a single batch request.
     * The canonical `options` column (used for validation and stored
     * submission answers) is never modified by translation.
     */
    protected function additionalTranslationPayload(): array
    {
        $payload = [];

        foreach ($this->options ?? [] as $index => $option) {
            if (filled($option)) {
                $payload["option_{$index}"] = $option;
            }
        }

        return $payload;
    }

    protected function applyAdditionalTranslationResult(array $result): void
    {
        if (empty($this->options)) {
            return;
        }

        $optionsEn = [];
        foreach ($this->options as $index => $option) {
            $optionsEn[$index] = $result["option_{$index}"] ?? null;
        }

        $this->options_en = $optionsEn;
    }
}
