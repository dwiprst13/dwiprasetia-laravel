<?php

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

it('allows admin to create posts with category and tags', function () {
    Storage::fake('public');

    $admin = User::factory()->create(['role' => 'admin']);
    $category = Category::factory()->create();
    [$laravelTag, $phpTag] = Tag::factory()->count(2)->create();

    Sanctum::actingAs($admin);

    $response = $this->postJson('/api/v1/admin/posts', [
        'title' => 'Build REST API with Laravel',
        'content' => 'Long form content about building APIs.',
        'status' => 'published',
        'excerpt' => 'How to build a REST API with Laravel.',
        'featured_image' => UploadedFile::fake()->image('cover.jpg'),
        'featured_image_alt' => 'Laptop showing Laravel logo',
        'category_slug' => $category->slug,
        'tags' => [$laravelTag->slug, $phpTag->slug],
    ]);

    $response->assertCreated();

    $post = Post::firstOrFail();
    expect($post->category_slug)->toBe($category->slug);
    expect($post->tags)->toMatchArray([$laravelTag->slug, $phpTag->slug]);
    expect($post->excerpt)->toBe('How to build a REST API with Laravel.');
    expect($post->featured_image_alt)->toBe('Laptop showing Laravel logo');

    Storage::disk('public')->assertExists($post->featured_image);
});

it('allows admin to update category and tags on posts', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $originalCategory = Category::factory()->create();
    $newCategory = Category::factory()->create();
    [$laravelTag, $vueTag] = Tag::factory()->count(2)->create();

    $post = Post::factory()
        ->for($admin, 'author')
        ->create([
            'status' => 'published',
            'category_slug' => $originalCategory->slug,
            'tags' => [$laravelTag->slug],
        ]);

    Sanctum::actingAs($admin);

    $response = $this->patchJson("/api/v1/admin/posts/{$post->id}", [
        'category_slug' => $newCategory->slug,
        'tags' => [$vueTag->slug],
        'excerpt' => 'Updated summary',
    ]);

    $response->assertOk();

    $post->refresh();
    expect($post->category_slug)->toBe($newCategory->slug);
    expect($post->tags)->toMatchArray([$vueTag->slug]);
    expect($post->excerpt)->toBe('Updated summary');
});

it('filters posts by category and tags', function () {
    $category = Category::factory()->create(['slug' => 'technology']);
    $otherCategory = Category::factory()->create(['slug' => 'lifestyle']);

    $laravelTag = Tag::factory()->create(['slug' => 'laravel']);
    $vueTag = Tag::factory()->create(['slug' => 'vue']);

    $matchingPost = Post::factory()->create([
        'status' => 'published',
        'category_slug' => $category->slug,
        'tags' => [$laravelTag->slug, $vueTag->slug],
    ]);

    Post::factory()->create([
        'status' => 'published',
        'category_slug' => $otherCategory->slug,
        'tags' => [$vueTag->slug],
    ]);

    $response = $this->getJson('/api/v1/posts?category_slug=technology&tag=laravel');

    $response->assertOk();

    $ids = collect($response->json('data'))->pluck('id');
    expect($ids)->toContain($matchingPost->id);
    expect($ids)->toHaveCount(1);
});
