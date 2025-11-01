<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tag\StoreTagRequest;
use App\Http\Requests\Tag\UpdateTagRequest;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TagController extends Controller
{
    public function index(Request $request)
    {
        $query = Tag::query();

        if ($search = $request->input('search')) {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $perPage = (int) min(max($request->integer('per_page', 15) ?: 15, 1), 100);

        $tags = $query->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return TagResource::collection($tags);
    }

    public function store(StoreTagRequest $request)
    {
        $data = $request->validated();
        $data['slug'] = $this->prepareSlug($data['slug'] ?? null, $data['name']);

        $tag = Tag::create($data);

        return TagResource::make($tag)->response()->setStatusCode(201);
    }

    public function show(Tag $tag)
    {
        return TagResource::make($tag);
    }

    public function update(UpdateTagRequest $request, Tag $tag)
    {
        $data = $request->validated();

        if (array_key_exists('slug', $data)) {
            $data['slug'] = $this->prepareSlug($data['slug'], $data['name'] ?? $tag->name, $tag->id);
        }

        $tag->fill($data);
        $tag->save();

        return TagResource::make($tag);
    }

    public function destroy(Tag $tag)
    {
        $tag->delete();

        return response()->json([
            'message' => 'Tag deleted.',
        ]);
    }

    protected function prepareSlug(?string $slug, string $fallback, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($slug ?: $fallback) ?: Str::random(8);
        $uniqueSlug = $baseSlug;
        $counter = 1;

        while (Tag::where('slug', $uniqueSlug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $uniqueSlug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $uniqueSlug;
    }
}
