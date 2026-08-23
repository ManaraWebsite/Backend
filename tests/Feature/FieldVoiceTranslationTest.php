<?php

namespace Tests\Feature;

use App\Jobs\TranslateContentJob;
use App\Models\FieldVoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Fakes\FakeTranslationService;
use Tests\TestCase;

class FieldVoiceTranslationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Model factories trigger the same translation-dispatch hook as the
        // real app. Fake the queue up front so no factory-created fixture
        // ever runs a real (synchronous, in tests) translation job.
        Queue::fake();
    }

    public function test_creating_a_field_voice_dispatches_a_translation_job(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/admin/field-voices', [
            'name' => 'Sara Ahmad',
            'role' => 'متطوعة',
            'quote' => 'نص الشهادة من الميدان.',
        ]);

        $response->assertCreated();

        $voice = FieldVoice::firstOrFail();
        $this->assertSame('متطوعة', $voice->role_ar);
        $this->assertSame('نص الشهادة من الميدان.', $voice->quote_ar);
        $this->assertSame('pending', $voice->translation_status);

        Queue::assertPushed(
            TranslateContentJob::class,
            fn ($job) => $job->modelClass === FieldVoice::class && $job->modelId === $voice->id
        );
    }

    public function test_translation_job_translates_quote_and_role(): void
    {
        $voice = FieldVoice::factory()->create([
            'role_ar' => 'متطوعة',
            'quote_ar' => 'نص الشهادة',
            'role_en' => null,
            'quote_en' => null,
        ]);

        (new TranslateContentJob(FieldVoice::class, $voice->id))->handle(new FakeTranslationService);

        $voice->refresh();
        $this->assertSame('[EN] متطوعة', $voice->role_en);
        $this->assertSame('[EN] نص الشهادة', $voice->quote_en);
        $this->assertSame('completed', $voice->translation_status);
    }

    public function test_updating_unrelated_field_does_not_dispatch_a_translation_job(): void
    {
        $admin = User::factory()->admin()->create();
        $voice = FieldVoice::factory()->create(['translation_status' => 'completed']);

        // Reset the fake — creating the fixture above legitimately dispatched
        // its own job; we only care about what happens on this update.
        Queue::fake();

        $response = $this->actingAs($admin)->putJson("/api/admin/field-voices/{$voice->id}", [
            'is_published' => false,
        ]);

        $response->assertOk();
        Queue::assertNotPushed(TranslateContentJob::class);
    }
}
