<?php

namespace App\Providers;

use App\Services\Translation\GeminiTranslationService;
use App\Services\Translation\TranslationServiceInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(TranslationServiceInterface::class, function () {
            return match (config('services.translation.provider')) {
                'gemini' => new GeminiTranslationService(
                    apiKey: config('services.gemini.key'),
                    model: config('services.gemini.translation_model'),
                ),
                default => throw new \InvalidArgumentException(
                    'Unsupported TRANSLATION_PROVIDER: '.config('services.translation.provider')
                ),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
