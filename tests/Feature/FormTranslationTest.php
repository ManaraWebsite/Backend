<?php

namespace Tests\Feature;

use App\Jobs\TranslateContentJob;
use App\Models\Form;
use App\Models\FormField;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Fakes\FakeTranslationService;
use Tests\TestCase;

class FormTranslationTest extends TestCase
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

    public function test_creating_a_form_dispatches_translation_jobs_for_form_and_fields(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/admin/forms', [
            'title' => 'نموذج التسجيل',
            'description' => 'صف النموذج',
            'is_active' => true,
            'fields' => [
                [
                    'label' => 'الجنس',
                    'type' => 'select',
                    'options' => ['ذكر', 'أنثى'],
                    'is_required' => true,
                    'order' => 1,
                ],
            ],
        ]);

        $response->assertCreated();

        $form = Form::firstOrFail();
        $field = $form->fields()->firstOrFail();

        $this->assertSame('نموذج التسجيل', $form->title_ar);
        $this->assertSame('pending', $form->translation_status);
        $this->assertSame('الجنس', $field->label_ar);
        $this->assertSame(['ذكر', 'أنثى'], $field->options);
        $this->assertSame('pending', $field->translation_status);

        Queue::assertPushed(TranslateContentJob::class, fn ($job) => $job->modelClass === Form::class);
        Queue::assertPushed(TranslateContentJob::class, fn ($job) => $job->modelClass === FormField::class);
    }

    public function test_translation_job_translates_form_title_and_description(): void
    {
        $form = Form::factory()->create([
            'title_ar' => 'نموذج',
            'description_ar' => 'وصف',
            'title_en' => null,
            'description_en' => null,
        ]);

        (new TranslateContentJob(Form::class, $form->id))->handle(new FakeTranslationService);

        $form->refresh();
        $this->assertSame('[EN] نموذج', $form->title_en);
        $this->assertSame('[EN] وصف', $form->description_en);
        $this->assertSame('completed', $form->translation_status);
    }

    public function test_translation_job_translates_field_label_and_options_preserving_internal_values(): void
    {
        $field = FormField::factory()->create([
            'label_ar' => 'الجنس',
            'options' => ['ذكر', 'أنثى', 'أخرى'],
        ]);

        (new TranslateContentJob(FormField::class, $field->id))->handle(new FakeTranslationService);

        $field->refresh();
        $this->assertSame('[EN] الجنس', $field->label_en);
        // Canonical options used for validation/submission are untouched.
        $this->assertSame(['ذكر', 'أنثى', 'أخرى'], $field->options);
        $this->assertSame(['[EN] ذكر', '[EN] أنثى', '[EN] أخرى'], $field->options_en);
        $this->assertSame('completed', $field->translation_status);
    }

    public function test_updating_form_active_flag_only_does_not_dispatch_translation(): void
    {
        $admin = User::factory()->admin()->create();
        $form = Form::factory()->create(['is_active' => false, 'translation_status' => 'completed']);

        // Reset the fake — creating the fixture above legitimately dispatched
        // its own job; we only care about what happens on this update.
        Queue::fake();

        $response = $this->actingAs($admin)->putJson("/api/admin/forms/{$form->slug}", [
            'is_active' => true,
        ]);

        $response->assertOk();
        Queue::assertNotPushed(TranslateContentJob::class);
    }

    public function test_api_resource_returns_bilingual_structure(): void
    {
        $form = Form::factory()->create([
            'title_ar' => 'نموذج',
            'title_en' => 'Form',
            'description_ar' => 'وصف',
            'description_en' => 'Description',
        ]);

        FormField::factory()->create([
            'form_id' => $form->id,
            'label_ar' => 'الجنس',
            'label_en' => 'Gender',
            'type' => 'select',
            'options' => ['ذكر', 'أنثى'],
            'options_en' => ['Male', 'Female'],
        ]);

        $form->update(['is_active' => true]);

        $response = $this->getJson("/api/forms/{$form->slug}");

        $response->assertOk();
        $response->assertJsonPath('data.title', ['ar' => 'نموذج', 'en' => 'Form']);
        $response->assertJsonPath('data.fields.0.label', ['ar' => 'الجنس', 'en' => 'Gender']);
        $response->assertJsonPath('data.fields.0.options.0', ['ar' => 'ذكر', 'en' => 'Male']);
        $response->assertJsonPath('data.fields.0.options.1', ['ar' => 'أنثى', 'en' => 'Female']);
    }
}
