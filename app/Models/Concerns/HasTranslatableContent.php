<?php

namespace App\Models\Concerns;

use App\Jobs\TranslateContentJob;

/**
 * Gives a model Arabic -> English auto-translation.
 *
 * Models using this trait store each translatable field as a pair of
 * columns, `{field}_ar` and `{field}_en`, plus a `translation_status`
 * column. Implementers list the base field names in translatableFields().
 *
 * To translate additional non-column data (see FormField's `options`
 * array), override the protected `additional*`/`hasAdditional*` hooks
 * below rather than the public methods directly — traits can't be
 * reached via `parent::` once a class redeclares one of their methods.
 */
trait HasTranslatableContent
{
    public static function bootHasTranslatableContent(): void
    {
        static::saved(function (self $model) {
            if (! $model->hasDirtyTranslatableFields()) {
                return;
            }

            if ($model->translation_status !== 'pending') {
                $model->forceFill(['translation_status' => 'pending'])->saveQuietly();
            }

            TranslateContentJob::dispatch(static::class, $model->getKey());
        });
    }

    /**
     * Base names of translatable fields, e.g. ['title', 'content'].
     * Each entry maps to `{name}_ar` and `{name}_en` columns.
     *
     * @return array<int, string>
     */
    protected function translatableFields(): array
    {
        return [];
    }

    public function hasDirtyTranslatableFields(): bool
    {
        // Eloquent only calls syncChanges() on the update path, so
        // wasChanged() is always false right after an insert — treat a
        // freshly created record with any Arabic content as dirty.
        if ($this->wasRecentlyCreated) {
            return filled($this->translationPayload());
        }

        foreach ($this->translatableFields() as $field) {
            if ($this->wasChanged("{$field}_ar")) {
                return true;
            }
        }

        return $this->hasAdditionalDirtyTranslatableContent();
    }

    protected function hasAdditionalDirtyTranslatableContent(): bool
    {
        return false;
    }

    /**
     * The Arabic content to send to the translation provider.
     *
     * @return array<string, string>
     */
    public function translationPayload(): array
    {
        $payload = [];

        foreach ($this->translatableFields() as $field) {
            $value = $this->{"{$field}_ar"};

            if (filled($value)) {
                $payload[$field] = $value;
            }
        }

        return array_merge($payload, $this->additionalTranslationPayload());
    }

    /**
     * @return array<string, string>
     */
    protected function additionalTranslationPayload(): array
    {
        return [];
    }

    /**
     * Apply a translation result (same keys as translationPayload()) to the model.
     *
     * @param  array<string, string>  $result
     */
    public function applyTranslationResult(array $result): void
    {
        foreach ($this->translatableFields() as $field) {
            if (array_key_exists($field, $result)) {
                $this->{"{$field}_en"} = $result[$field];
            }
        }

        $this->applyAdditionalTranslationResult($result);
    }

    /**
     * @param  array<string, string>  $result
     */
    protected function applyAdditionalTranslationResult(array $result): void
    {
        //
    }
}
