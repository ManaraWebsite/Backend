<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Support\Str;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return PostResource::collection(Post::with('author')->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePostRequest $request)
    {
        $validatedData = $request->validated();

        $validatedData['author_id'] = $request->user()->id;

        $validatedData['slug'] = Str::random(8).'-'.Str::slug($validatedData['title']);

        if ($validatedData['status'] === 'published') {
            $validatedData['published_at'] = now();
        }

        if ($request->hasFile('cover_image')) {
            $validatedData['cover_image'] = $request->file('cover_image')
                ->store('posts', 'public');
        }

        $validatedData['title_ar'] = $validatedData['title'];
        $validatedData['content_ar'] = $validatedData['content'];
        unset($validatedData['title'], $validatedData['content']);

        $post = Post::create($validatedData);

        return new PostResource($post);
    }

    /**
     * Display the specified resource.
     */
    public function show(Post $post)
    {
        $post->load('author');

        return new PostResource($post);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePostRequest $request, Post $post)
    {
        $validatedData = $request->validated();

        if ($request->has('status') && $validatedData['status'] === 'published') {
            $validatedData['published_at'] = now();
        }

        if ($request->hasFile('cover_image')) {
            $validatedData['cover_image'] = $request->file('cover_image')
                ->store('posts', 'public');
        }

        if (array_key_exists('title', $validatedData)) {
            $validatedData['title_ar'] = $validatedData['title'];
            unset($validatedData['title']);
        }

        if (array_key_exists('content', $validatedData)) {
            $validatedData['content_ar'] = $validatedData['content'];
            unset($validatedData['content']);
        }

        $post->update($validatedData);

        return new PostResource($post);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post)
    {
        $post->delete();

        return response()->json(null, 204);
    }

    public function publish(Post $post)
    {
        if ($post->isPublished()) {
            return response()->json(['message' => 'Post is already published.'], 400);
        }

        $post->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        return new PostResource($post);
    }

    public function unpublish(Post $post)
    {
        if (! $post->isPublished()) {
            return response()->json(['message' => 'Post is not published.'], 400);
        }

        $post->update([
            'status' => 'draft',
            'published_at' => null,
        ]);

        return new PostResource($post);
    }
}
