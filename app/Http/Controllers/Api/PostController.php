<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $query = Post::query()
            ->with(['author', 'category', 'tags']);

        $user = $request->user('sanctum') ?? $request->user();
        $status = $request->input('status');

        if ($user && $user->isAdmin()) {
            if ($status) {
                if (! in_array($status, ['published', 'draft', 'scheduled', 'archived', 'all'], true)) {
                    throw ValidationException::withMessages([
                        'status' => 'Status filter must be all, published, draft, scheduled, or archived.',
                    ]);
                }

                if ($status !== 'all') {
                    $query->where('status', $status);
                }
            }
        } else {
            $query->where('status', 'published');
        }

        if ($search = $request->input('search')) {
            $query->where(function ($builder) use ($search) {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($authorId = $request->integer('author_id')) {
            $query->where('user_id', $authorId);
        }

        if ($categoryId = $request->integer('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($tagIds = $request->input('tags')) {
            $tagIds = array_filter((array) $tagIds, fn ($tagId) => is_numeric($tagId));
            if ($tagIds) {
                $query->whereHas('tags', function ($builder) use ($tagIds) {
                    $builder->whereIn('tags.id', $tagIds);
                });
            }
        }

        $perPage = (int) min($request->integer('per_page', 10) ?: 10, 100);

        $posts = $query->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        return PostResource::collection($posts);
    }

    public function store(StorePostRequest $request)
    {
        $data = $request->validated();

        $data['slug'] = $data['slug'] ?? $this->makeUniqueSlug($data['title']);

        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = $request->file('featured_image')->store('posts', 'public');
        }

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $request->file('thumbnail')->store('posts', 'public');
        }

        if ($request->hasFile('og_image')) {
            $data['og_image'] = $request->file('og_image')->store('posts', 'public');
        }

        $tags = $data['tags'] ?? [];
        unset($data['tags']);

        $data['status'] = $data['status'] ?? 'draft';
        $data['allow_comments'] = $data['allow_comments'] ?? true;
        $data['reading_time'] = $data['reading_time'] ?? $this->estimateReadingTime($data['content'] ?? '');
        $this->applyStatusTimestamps($data);

        $data['user_id'] = $request->user()->id;

        $post = Post::create($data);

        if ($tags) {
            $post->tags()->sync($tags);
        }

        $post->load(['author', 'category', 'tags']);

        return PostResource::make($post)->response()->setStatusCode(201);
    }

    public function show(Request $request, Post $post)
    {
        $user = $request->user('sanctum') ?? $request->user();

        if ($post->status !== 'published' && (! $user || ! $user->isAdmin())) {
            abort(404);
        }

        if ($post->status === 'published') {
            $post->incrementViewCounter();
            $post->refresh();
        }

        $post->load(['author', 'category', 'tags']);

        return PostResource::make($post);
    }

    public function update(UpdatePostRequest $request, Post $post)
    {
        $data = $request->validated();

        if (array_key_exists('slug', $data)) {
            $data['slug'] = $data['slug'] ?: $this->makeUniqueSlug($data['title'] ?? $post->title, $post->id);
        }

        if ($request->hasFile('featured_image')) {
            $newPath = $request->file('featured_image')->store('posts', 'public');
            if ($post->featured_image) {
                Storage::disk('public')->delete($post->featured_image);
            }
            $data['featured_image'] = $newPath;
        } elseif (array_key_exists('featured_image', $data) && $data['featured_image'] === null && $post->featured_image) {
            Storage::disk('public')->delete($post->featured_image);
            $data['featured_image'] = null;
        }

        if ($request->hasFile('thumbnail')) {
            $newThumbnail = $request->file('thumbnail')->store('posts', 'public');
            if ($post->thumbnail) {
                Storage::disk('public')->delete($post->thumbnail);
            }
            $data['thumbnail'] = $newThumbnail;
        } elseif (array_key_exists('thumbnail', $data) && $data['thumbnail'] === null) {
            if ($post->thumbnail) {
                Storage::disk('public')->delete($post->thumbnail);
            }
            $data['thumbnail'] = null;
        }

        if ($request->hasFile('og_image')) {
            $newOgImage = $request->file('og_image')->store('posts', 'public');
            if ($post->og_image) {
                Storage::disk('public')->delete($post->og_image);
            }
            $data['og_image'] = $newOgImage;
        } elseif (array_key_exists('og_image', $data) && $data['og_image'] === null) {
            if ($post->og_image) {
                Storage::disk('public')->delete($post->og_image);
            }
            $data['og_image'] = null;
        }

        if (array_key_exists('content', $data) && empty($data['reading_time'])) {
            $data['reading_time'] = $this->estimateReadingTime($data['content']);
        }

        $this->applyStatusTimestamps($data, $post);

        $tags = null;
        if (array_key_exists('tags', $data)) {
            $tags = $data['tags'] ?? [];
            unset($data['tags']);
        }

        $post->fill($data);
        $post->save();

        if ($tags !== null) {
            $post->tags()->sync($tags);
        }

        $post->load(['author', 'category', 'tags']);

        return PostResource::make($post);
    }

    public function destroy(Post $post)
    {
        $post->delete();

        return response()->json([
            'message' => 'Post deleted.',
        ]);
    }

    protected function makeUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title) ?: Str::random(8);
        $slug = $baseSlug;
        $counter = 1;

        while ($this->slugExists($slug, $ignoreId)) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    protected function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        return Post::where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }

    protected function estimateReadingTime(string $content): int
    {
        $wordCount = str_word_count(strip_tags($content));

        return max(1, (int) ceil($wordCount / 200));
    }

    protected function applyStatusTimestamps(array &$data, ?Post $post = null): void
    {
        $status = $data['status'] ?? $post?->status ?? 'draft';
        $now = Carbon::now();

        if ($status === 'published') {
            $data['published_at'] = $data['published_at'] ?? $post?->published_at ?? $now;
            $data['scheduled_at'] = null;
        } elseif ($status === 'scheduled') {
            $data['scheduled_at'] = $data['scheduled_at'] ?? $post?->scheduled_at;
            $data['published_at'] = null;
        } else {
            if (! array_key_exists('published_at', $data)) {
                $data['published_at'] = ($status === 'archived') ? ($data['published_at'] ?? $post?->published_at) : null;
            }
            $data['scheduled_at'] = null;
        }

        if ($status === 'scheduled' && empty($data['scheduled_at'])) {
            throw ValidationException::withMessages([
                'scheduled_at' => 'Scheduled posts must include a schedule datetime.',
            ]);
        }
    }
}
