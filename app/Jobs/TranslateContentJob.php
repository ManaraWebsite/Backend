<?php

namespace App\Jobs;

use App\Services\Translation\TranslationServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class TranslateContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

    /**
     * @param  class-string  $modelClass  Fully-qualified model class, e.g. App\Models\Post.
     */
    public function __construct(
        public string $modelClass,
        public int|string $modelId,
    ) {}

    public function handle(TranslationServiceInterface $translator): void
    {
        $model = $this->modelClass::find($this->modelId);

        if (! $model) {
            return;
        }

        $model->forceFill(['translation_status' => 'processing'])->saveQuietly();

        $payload = $model->translationPayload();

        if (empty($payload)) {
            $model->forceFill(['translation_status' => 'completed'])->saveQuietly();

            return;
        }

        try {
            $translated = $translator->translate($payload, 'ar', 'en');

            $model->applyTranslationResult($translated);
            $model->translation_status = 'completed';
            $model->saveQuietly();
        } catch (Throwable $e) {
            Log::error('Content translation attempt failed.', [
                'model' => $this->modelClass,
                'model_id' => $this->modelId,
                'attempt' => $this->attempts(),
                'reason' => $e->getMessage(),
            ]);

            $model->forceFill(['translation_status' => 'failed'])->saveQuietly();

            throw $e;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->modelClass::find($this->modelId)
            ?->forceFill(['translation_status' => 'failed'])
            ->saveQuietly();
    }
}
