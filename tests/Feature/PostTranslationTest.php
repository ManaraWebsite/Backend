<?php

namespace Tests\Feature;

use App\Exceptions\TranslationFailedException;
use App\Jobs\TranslateContentJob;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Fakes\FakeTranslationService;
use Tests\TestCase;

class PostTranslationTest extends TestCase
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

    public function test_creating_a_post_saves_arabic_immediately_and_defers_english(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/admin/posts', [
            'title' => 'عنوان المنشور',
            'content' => 'هذا هو محتوى المنشور.',
            'status' => 'draft',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.title.ar', 'عنوان المنشور');
        $response->assertJsonPath('data.title.en', null);
        $response->assertJsonPath('data.translation_status', 'pending');

        $post = Post::firstOrFail();
        $this->assertSame('عنوان المنشور', $post->title_ar);
        $this->assertSame('هذا هو محتوى المنشور.', $post->content_ar);
        $this->assertNull($post->title_en);
        $this->assertSame('pending', $post->translation_status);

        Queue::assertPushed(
            TranslateContentJob::class,
            fn ($job) => $job->modelClass === Post::class && $job->modelId === $post->id
        );
    }

    public function test_translation_job_fills_in_english_fields_and_marks_completed(): void
    {
        $post = Post::factory()->create([
            'title_ar' => 'عنوان',
            'content_ar' => 'محتوى',
            'title_en' => null,
            'content_en' => null,
            'translation_status' => 'pending',
        ]);

        (new TranslateContentJob(Post::class, $post->id))->handle(new FakeTranslationService);

        $post->refresh();
        $this->assertSame('[EN] عنوان', $post->title_en);
        $this->assertSame('[EN] محتوى', $post->content_en);
        $this->assertSame('completed', $post->translation_status);
        // Arabic untouched
        $this->assertSame('عنوان', $post->title_ar);
        $this->assertSame('محتوى', $post->content_ar);
    }

    public function test_updating_arabic_content_dispatches_a_new_translation_job(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create(['translation_status' => 'completed']);

        $response = $this->actingAs($admin)->putJson("/api/admin/posts/{$post->slug}", [
            'title' => 'عنوان محدث',
        ]);

        $response->assertOk();

        $post->refresh();
        $this->assertSame('عنوان محدث', $post->title_ar);
        $this->assertSame('pending', $post->translation_status);

        Queue::assertPushed(TranslateContentJob::class);
    }

    public function test_updating_unrelated_fields_does_not_dispatch_a_translation_job(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create(['status' => 'draft', 'translation_status' => 'completed']);

        // Reset the fake — creating the fixture above legitimately dispatched
        // its own job; we only care about what happens on this update.
        Queue::fake();

        $response = $this->actingAs($admin)->putJson("/api/admin/posts/{$post->slug}", [
            'status' => 'published',
        ]);

        $response->assertOk();

        $post->refresh();
        $this->assertSame('published', $post->status);
        $this->assertSame('completed', $post->translation_status);

        Queue::assertNotPushed(TranslateContentJob::class);
    }

    public function test_translation_failure_keeps_arabic_content_and_marks_status_failed(): void
    {
        $fake = new FakeTranslationService;
        $fake->shouldFail = true;

        $post = Post::factory()->create([
            'title_ar' => 'عنوان',
            'content_ar' => 'محتوى',
            'title_en' => null,
        ]);

        try {
            (new TranslateContentJob(Post::class, $post->id))->handle($fake);
            $this->fail('Expected TranslationFailedException was not thrown.');
        } catch (TranslationFailedException) {
            // expected
        }

        $post->refresh();
        $this->assertSame('عنوان', $post->title_ar);
        $this->assertSame('محتوى', $post->content_ar);
        $this->assertNull($post->title_en);
        $this->assertSame('failed', $post->translation_status);
    }

    public function test_public_users_cannot_trigger_translation_via_the_api(): void
    {
        $response = $this->postJson('/api/admin/posts', [
            'title' => 'عنوان',
            'content' => 'محتوى',
            'status' => 'draft',
        ]);

        $response->assertUnauthorized();
        $this->assertSame(0, Post::count());
    }
}
