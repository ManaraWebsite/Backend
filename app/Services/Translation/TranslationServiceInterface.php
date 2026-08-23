<?php

namespace App\Services\Translation;

use App\Exceptions\TranslationFailedException;

interface TranslationServiceInterface
{
    /**
     * Translate a flat set of labelled strings in one batch request.
     *
     * @param  array<string, string>  $content  Map of field key => source text.
     * @return array<string, string> Map of the same field keys => translated text.
     *
     * @throws TranslationFailedException
     */
    public function translate(array $content, string $sourceLanguage = 'ar', string $targetLanguage = 'en'): array;
}
