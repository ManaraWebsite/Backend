<?php

namespace Tests\Fakes;

use App\Exceptions\TranslationFailedException;
use App\Services\Translation\TranslationServiceInterface;

class FakeTranslationService implements TranslationServiceInterface
{
    public bool $shouldFail = false;

    public function translate(array $content, string $sourceLanguage = 'ar', string $targetLanguage = 'en'): array
    {
        if ($this->shouldFail) {
            throw new TranslationFailedException('Simulated translation provider failure.');
        }

        return array_map(fn (string $value) => "[EN] {$value}", $content);
    }
}
