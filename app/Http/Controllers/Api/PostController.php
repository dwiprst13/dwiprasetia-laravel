<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $query = Post::query()
            ->with('author')
            ->withCount(['likes', 'comments']);

        $user = $request->user('sanctum') ?? $request->user();
        $status = $request->input('status');

        if ($user && $user->isAdmin()) {
            if ($status) {
                if (! in_array($status, ['published', 'draft', 'all'], true)) {
                    throw ValidationException::withMessages([
                        'status' => 'Status filter must be all, published, or draft.',
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

        $perPage = (int) min($request->integer('per_page', 10) ?: 10, 100);

        $posts = $query->orderByDesc('created_at')->paginate($perPage)->withQueryString();

        return PostResource::collection($posts);
    }

    public function store(StorePostRequest $request)
    {
        $data = $request->validated();

        $data['slug'] = $data['slug'] ?? $this->makeUniqueSlug($data['title']);

        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = $request->file('featured_image')->store('posts', 'public');
        }

        $data['user_id'] = $request->user()->id;

        $post = Post::create($data);
        $post->load('author')->loadCount(['likes', 'comments']);

        return PostResource::make($post)->response()->setStatusCode(201);
    }

    public function show(Request $request, Post $post)
    {
        $user = $request->user('sanctum') ?? $request->user();

        if ($post->status !== 'published' && (! $user || ! $user->isAdmin())) {
            abort(404);
        }

        $post->load('author')->loadCount(['likes', 'comments']);

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
        }

        $post->fill($data);
        $post->save();

        $post->load('author')->loadCount(['likes', 'comments']);

        return PostResource::make($post);
    }

    public function destroy(Post $post)
    {
        if ($post->featured_image) {
            Storage::disk('public')->delete($post->featured_image);
        }

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
}
