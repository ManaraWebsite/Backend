<?php

namespace Tests\Feature;

use App\Http\Resources\FormResource;
use App\Models\Form;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FormImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Factories dispatch translation jobs; keep them off the real queue.
        Queue::fake();
    }

    /**
     * A valid upload without relying on the GD extension, which
     * UploadedFile::fake()->image() requires and CI may not have.
     */
    private function fakeImage(string $name = 'banner.png', int $kilobytes = 10): UploadedFile
    {
        return UploadedFile::fake()->create($name, $kilobytes, 'image/png');
    }

    public function test_a_form_can_be_created_with_an_image(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/api/admin/forms', [
            'title' => 'نموذج التسجيل',
            'is_active' => true,
            'image' => $this->fakeImage(),
            'fields' => [
                ['label' => 'الاسم', 'type' => 'text', 'is_required' => true],
            ],
        ]);

        $response->assertCreated();

        $form = Form::firstOrFail();

        $this->assertStringStartsWith('forms/', $form->image);
        Storage::disk('public')->assertExists($form->image);
        $response->assertJsonPath('data.image', Storage::disk('public')->url($form->image));
    }

    /**
     * Sending a file forces multipart/form-data, which encodes nested data as
     * `fields[0][label]` rather than JSON. This is the payload the admin UI
     * actually sends, options and booleans included.
     */
    public function test_nested_fields_survive_a_multipart_create(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/api/admin/forms', [
            'title' => 'نموذج التسجيل',
            'is_active' => true,
            'image' => $this->fakeImage(),
            'fields' => [
                ['label' => 'الاسم', 'type' => 'text', 'is_required' => true, 'order' => 1],
                ['label' => 'المسار', 'type' => 'select', 'options' => ['خلفية', 'واجهة'], 'order' => 2],
            ],
        ]);

        $response->assertCreated();

        $form = Form::with('fields')->firstOrFail();

        // `is_active` is not cast on the model, so the DB hands back 1/0.
        $this->assertTrue((bool) $form->is_active);
        $this->assertCount(2, $form->fields);

        $select = $form->fields->firstWhere('type', 'select');
        $this->assertSame(['خلفية', 'واجهة'], $select->options);

        $text = $form->fields->firstWhere('type', 'text');
        $this->assertTrue((bool) $text->is_required);
    }

    public function test_a_form_without_an_image_returns_null(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/admin/forms', [
            'title' => 'نموذج بدون صورة',
            'fields' => [
                ['label' => 'الاسم', 'type' => 'text'],
            ],
        ]);

        $response->assertCreated()->assertJsonPath('data.image', null);
    }

    public function test_updating_a_form_without_an_image_keeps_the_existing_one(): void
    {
        $admin = User::factory()->admin()->create();
        $form = Form::factory()->create(['image' => 'forms/original.png']);

        $response = $this->actingAs($admin)
            ->putJson("/api/admin/forms/{$form->slug}", ['title' => 'عنوان جديد']);

        $response->assertOk();
        $this->assertSame('forms/original.png', $form->fresh()->image);
    }

    public function test_updating_a_form_with_a_new_image_replaces_it(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $form = Form::factory()->create(['image' => 'forms/original.png']);

        $response = $this->actingAs($admin)->post("/api/admin/forms/{$form->slug}", [
            '_method' => 'PUT',
            'title' => 'عنوان جديد',
            'image' => $this->fakeImage('replacement.png'),
        ]);

        $response->assertOk();

        $image = $form->fresh()->image;
        $this->assertNotSame('forms/original.png', $image);
        Storage::disk('public')->assertExists($image);
    }

    public function test_a_non_image_upload_is_rejected(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/api/admin/forms', [
            'title' => 'ملف غير صالح',
            'image' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
            'fields' => [
                ['label' => 'الاسم', 'type' => 'text'],
            ],
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('image');
        $this->assertNull(Form::first());
    }

    public function test_an_oversized_image_is_rejected(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/api/admin/forms', [
            'title' => 'صورة كبيرة',
            'image' => $this->fakeImage('huge.png', 3000),
            'fields' => [
                ['label' => 'الاسم', 'type' => 'text'],
            ],
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('image');
    }

    /**
     * Runs against the real `public` disk config (no Storage::fake, which
     * drops the disk's configured URL) so the front end's actual contract —
     * an absolute URL — is what gets asserted.
     */
    public function test_the_api_returns_an_absolute_url_for_a_stored_image(): void
    {
        $admin = User::factory()->admin()->create();
        $form = Form::factory()->create(['image' => 'forms/original.png']);

        $response = $this->actingAs($admin)
            ->postJson("/api/admin/forms/{$form->slug}/duplicate");

        $response->assertCreated()->assertJsonPath(
            'data.image',
            rtrim(config('app.url'), '/').'/storage/forms/original.png'
        );
    }

    public function test_an_image_that_is_already_a_url_is_passed_through_unchanged(): void
    {
        $external = 'https://picsum.photos/seed/abc/800/400';
        $form = Form::factory()->make(['image' => $external]);

        $payload = (new FormResource($form))->toArray(Request::create('/api/forms/x'));

        $this->assertSame($external, $payload['image']);
    }
}
