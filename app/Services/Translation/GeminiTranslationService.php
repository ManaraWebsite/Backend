<?php

namespace App\Services\Translation;

use App\Exceptions\TranslationFailedException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GeminiTranslationService implements TranslationServiceInterface
{
    private const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct(
        protected readonly ?string $apiKey,
        protected readonly string $model,
    ) {}

    public function translate(array $content, string $sourceLanguage = 'ar', string $targetLanguage = 'en'): array
    {
        if (empty($content)) {
            return [];
        }

        if (blank($this->apiKey)) {
            throw new TranslationFailedException('Translation provider is not configured.');
        }

        try {
            $response = Http::withHeaders([
                'x-goog-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])
                ->timeout(30)
                ->retry(2, 500, throw: false)
                ->post(self::ENDPOINT."/{$this->model}:generateContent", [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                ['text' => $this->buildPrompt($content, $sourceLanguage, $targetLanguage)],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.2,
                        'responseMimeType' => 'application/json',
                    ],
                ]);
        } catch (Throwable $e) {
            Log::error('Translation provider request failed.', ['provider' => 'gemini', 'reason' => $e->getMessage()]);

            throw new TranslationFailedException('Unable to reach the translation provider.', previous: $e);
        }

        if ($response->failed()) {
            Log::error('Translation provider returned an error response.', [
                'provider' => 'gemini',
                'status' => $response->status(),
            ]);

            throw new TranslationFailedException("Translation provider responded with status {$response->status()}.");
        }

        $text = $response->json('candidates.0.content.parts.0.text');

        if (! is_string($text) || trim($text) === '') {
            Log::error('Translation provider returned an empty response.', ['provider' => 'gemini']);

            throw new TranslationFailedException('Translation provider returned an empty response.');
        }

        $translated = json_decode($text, true);

        if (! is_array($translated) || json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Translation provider returned malformed JSON.', ['provider' => 'gemini']);

            throw new TranslationFailedException('Translation provider returned malformed JSON.');
        }

        $result = [];
        foreach (array_keys($content) as $key) {
            $value = $translated[$key] ?? null;

            if (! is_string($value) || trim($value) === '') {
                Log::error('Translation provider response is missing a required field.', [
                    'provider' => 'gemini',
                    'field' => $key,
                ]);

                throw new TranslationFailedException("Translation provider response is missing the '{$key}' field.");
            }

            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * @param  array<string, string>  $content
     */
    private function buildPrompt(array $content, string $sourceLanguage, string $targetLanguage): string
    {
        $payload = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return <<<PROMPT
            You are a professional translator. Translate the string values in the JSON object below
            from {$sourceLanguage} to {$targetLanguage}.

            Rules:
            - Preserve the original meaning; do not add or remove information.
            - Use natural, professional {$targetLanguage} rather than literal word-for-word translation.
            - Preserve names, organizations, locations, numbers, dates, and technical terminology.
            - Preserve URLs and do not translate code or HTML tags; preserve HTML attributes exactly.
            - Preserve Markdown syntax if present; translate only the human-readable text.
            - Return ONLY a single JSON object with exactly the same keys as the input.
            - Each value must be the translated string for that key. Do not add, remove, or rename keys.
            - Do not wrap the JSON in markdown code fences and do not include any commentary.

            Input JSON:
            {$payload}
            PROMPT;
    }
}
