<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class FormCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_visitors_can_list_only_active_forms_without_authentication(): void
    {
        $published = Form::factory()->create(['is_active' => true]);
        Form::factory()->create(['is_active' => false]);
        $this->getJson('/api/forms')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.slug', $published->slug);
    }

    public function test_image_removal_clears_only_the_selected_form_reference(): void
    {
        $admin = User::factory()->admin()->create();
        $form = Form::factory()->create(['image' => 'forms/shared.png']);
        $duplicate = Form::factory()->create(['image' => 'forms/shared.png']);
        $this->actingAs($admin)->postJson("/api/admin/forms/{$form->slug}", [
            '_method' => 'PUT', 'remove_image' => '1',
        ])->assertOk()->assertJsonPath('data.image', null);
        $this->assertNull($form->fresh()->image);
        $this->assertSame('forms/shared.png', $duplicate->fresh()->image);
    }

    public function test_false_removal_flag_keeps_existing_image(): void
    {
        $admin = User::factory()->admin()->create();
        $form = Form::factory()->create(['image' => 'forms/existing.png']);
        $this->actingAs($admin)->putJson("/api/admin/forms/{$form->slug}", [
            'remove_image' => false,
        ])->assertOk();
        $this->assertSame('forms/existing.png', $form->fresh()->image);
    }

    public function test_image_removal_requires_authentication(): void
    {
        $form = Form::factory()->create(['image' => 'forms/existing.png']);
        $this->putJson("/api/admin/forms/{$form->slug}", ['remove_image' => true])->assertUnauthorized();
        $this->assertSame('forms/existing.png', $form->fresh()->image);
    }

    public function test_editing_preserves_field_identity_and_existing_answers(): void
    {
        $admin = User::factory()->admin()->create();
        $form = Form::factory()->create(['slug' => 'stable-workshop']);
        $field = \App\Models\FormField::factory()->create(['form_id' => $form->id]);
        $submission = \App\Models\FormSubmission::factory()->create(['form_id' => $form->id]);
        $answer = \App\Models\SubmissionAnswer::factory()->create([
            'submission_id' => $submission->id, 'field_id' => $field->id, 'answer' => 'Existing answer',
        ]);
        $this->actingAs($admin)->postJson("/api/admin/forms/{$form->slug}", [
            '_method' => 'PUT', 'slug' => $form->slug, 'is_active' => '0',
            'fields' => [['id' => $field->id, 'label' => 'Updated label', 'type' => 'text', 'is_required' => '1']],
        ])->assertOk()->assertJsonPath('data.slug', 'stable-workshop');
        $this->assertSame($field->id, $form->fresh()->fields->sole()->id);
        $this->assertSame('Existing answer', $answer->fresh()->answer);
        $this->assertFalse((bool) $form->fresh()->is_active);
    }
}
