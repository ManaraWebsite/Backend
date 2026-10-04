<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\SubmissionAnswer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class FormFileUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Factories dispatch translation jobs; keep them off the real queue.
        Queue::fake();
    }

    private function fakePdf(string $name = 'cv.pdf', int $kilobytes = 100): UploadedFile
    {
        return UploadedFile::fake()->create($name, $kilobytes, 'application/pdf');
    }

    private function formWithFileField(bool $required = true): array
    {
        $form = Form::factory()->create(['is_active' => true]);

        $field = FormField::factory()->create([
            'form_id' => $form->id,
            'label_ar' => 'السيرة الذاتية',
            'type' => 'file',
            'is_required' => $required,
        ]);

        return [$form, $field];
    }

    private function submissionWithFile(Form $form, FormField $field, string $path): SubmissionAnswer
    {
        $submission = FormSubmission::create(['form_id' => $form->id, 'submitted_at' => now()]);

        return SubmissionAnswer::create([
            'submission_id' => $submission->id,
            'field_id' => $field->id,
            'answer' => $path,
        ]);
    }

    public function test_an_admin_can_add_a_file_field_to_a_form(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/admin/forms', [
            'title' => 'نموذج التقديم',
            'fields' => [
                ['label' => 'السيرة الذاتية', 'type' => 'file', 'is_required' => true],
            ],
        ]);

        $response->assertCreated()->assertJsonPath('data.fields.0.type', 'file');
    }

    public function test_a_pdf_is_stored_privately_when_submitted(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        [$form, $field] = $this->formWithFileField();
        $name = FormField::factory()->create(['form_id' => $form->id, 'order' => 2]);

        $response = $this->post("/api/forms/{$form->slug}/submit", [
            'answers' => [
                $field->id => $this->fakePdf(),
                $name->id => 'أحمد',
            ],
        ]);

        $response->assertCreated();

        $path = SubmissionAnswer::where('field_id', $field->id)->value('answer');

        $this->assertStringStartsWith("submissions/{$form->id}/", $path);
        $this->assertStringEndsWith('.pdf', $path);
        Storage::disk('local')->assertExists($path);
        $this->assertEmpty(Storage::disk('public')->allFiles());

        $this->assertSame('أحمد', SubmissionAnswer::where('field_id', $name->id)->value('answer'));
    }

    public function test_a_non_pdf_upload_is_rejected(): void
    {
        Storage::fake('local');
        [$form, $field] = $this->formWithFileField();

        $response = $this->post("/api/forms/{$form->slug}/submit", [
            'answers' => [$field->id => UploadedFile::fake()->create('photo.png', 10, 'image/png')],
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors("answers.{$field->id}");
        $this->assertSame(0, FormSubmission::count());
        $this->assertEmpty(Storage::disk('local')->allFiles());
    }

    public function test_a_pdf_over_5mb_is_rejected(): void
    {
        Storage::fake('local');
        [$form, $field] = $this->formWithFileField();

        $response = $this->post("/api/forms/{$form->slug}/submit", [
            'answers' => [$field->id => $this->fakePdf('huge.pdf', 5 * 1024 + 1)],
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors("answers.{$field->id}");
    }

    public function test_text_is_rejected_for_a_file_field(): void
    {
        [$form, $field] = $this->formWithFileField();

        $response = $this->postJson("/api/forms/{$form->slug}/submit", [
            'answers' => [$field->id => 'cv.pdf'],
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors("answers.{$field->id}");
    }

    public function test_a_required_file_field_must_be_provided(): void
    {
        [$form, $field] = $this->formWithFileField(required: true);

        $response = $this->postJson("/api/forms/{$form->slug}/submit", ['answers' => []]);

        $response->assertUnprocessable()->assertJsonValidationErrors("answers.{$field->id}");
    }

    public function test_an_optional_file_field_can_be_left_empty(): void
    {
        [$form, $field] = $this->formWithFileField(required: false);

        $response = $this->postJson("/api/forms/{$form->slug}/submit", ['answers' => []]);

        $response->assertCreated();
        $this->assertSame(0, SubmissionAnswer::where('field_id', $field->id)->count());
    }

    public function test_admins_get_an_expiring_link_instead_of_the_stored_path(): void
    {
        $this->freezeTime();
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        [$form, $field] = $this->formWithFileField();
        $this->submissionWithFile($form, $field, 'submissions/1/cv.pdf');

        $response = $this->actingAs($admin)->getJson("/api/admin/forms/{$form->slug}/submissions");

        $response->assertOk()
            ->assertJsonPath('submissions.0.answers.0.field_type', 'file')
            ->assertJsonPath(
                'submissions.0.answers.0.answer',
                Storage::disk('local')->temporaryUrl('submissions/1/cv.pdf', now()->addHour())
            );
    }

    public function test_the_csv_export_links_to_uploaded_files(): void
    {
        $this->freezeTime();
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        [$form, $field] = $this->formWithFileField();
        $this->submissionWithFile($form, $field, 'submissions/1/cv.pdf');

        $csv = $this->actingAs($admin)
            ->get("/api/admin/forms/{$form->slug}/submissions/export")
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString(
            Storage::disk('local')->temporaryUrl('submissions/1/cv.pdf', now()->addDays(7)),
            $csv
        );
    }

    /**
     * Storage::fake swaps temporaryUrl for an unsigned stand-in, so the real
     * signed route is built by hand to prove the file is served through it.
     */
    public function test_an_uploaded_pdf_is_only_served_through_a_valid_signed_link(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('submissions/1/cv.pdf', '%PDF-1.4 test');

        $signed = URL::temporarySignedRoute(
            'storage.local',
            now()->addHour(),
            ['path' => 'submissions/1/cv.pdf'],
            absolute: false
        );

        $this->get($signed)->assertOk();
        $this->get('/storage/submissions/1/cv.pdf')->assertForbidden();

        $this->travel(2)->hours();
        $this->get($signed)->assertForbidden();
    }
}
