<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::query();

        if ($search = $request->input('search')) {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $perPage = (int) min(max($request->integer('per_page', 15) ?: 15, 1), 100);

        $categories = $query->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return CategoryResource::collection($categories);
    }

    public function store(StoreCategoryRequest $request)
    {
        $data = $request->validated();

        $data['slug'] = $this->prepareSlug($data['slug'] ?? null, $data['name']);

        $category = Category::create($data);

        return CategoryResource::make($category)->response()->setStatusCode(201);
    }

    public function show(Category $category)
    {
        return CategoryResource::make($category);
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $data = $request->validated();

        if (array_key_exists('slug', $data)) {
            $data['slug'] = $this->prepareSlug($data['slug'], $data['name'] ?? $category->name, $category->id);
        }

        $category->fill($data);
        $category->save();

        return CategoryResource::make($category);
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return response()->json([
            'message' => 'Category deleted.',
        ]);
    }

    protected function prepareSlug(?string $slug, string $fallback, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($slug ?: $fallback) ?: Str::random(8);
        $uniqueSlug = $baseSlug;
        $counter = 1;

        while (Category::where('slug', $uniqueSlug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $uniqueSlug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $uniqueSlug;
    }
}
