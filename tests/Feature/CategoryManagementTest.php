<?php

use App\Models\Category;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('lists categories with search filters', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $matched = Category::factory()->create(['name' => 'Tech News', 'slug' => 'tech-news']);
    $other = Category::factory()->create(['name' => 'Travel Tips', 'slug' => 'travel-tips']);

    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/v1/admin/categories?search=tech');

    $response->assertOk();

    $data = $response->json('data');
    expect($data)->toBeArray();
    $ids = collect($data)->pluck('id');
    expect($ids)->toContain($matched->id);
    expect($ids)->not->toContain($other->id);
});

it('creates a new category and normalizes slug', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($admin);

    $response = $this->postJson('/api/v1/admin/categories', [
        'name' => 'Business Insights',
        'slug' => 'Business Insights',
        'description' => 'Articles about running a business.',
    ]);

    $response->assertCreated();

    $this->assertDatabaseHas('category', [
        'name' => 'Business Insights',
        'slug' => 'business-insights',
    ]);
});

it('updates a category and regenerates slug when needed', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $category = Category::factory()->create([
        'name' => 'Lifestyle',
        'slug' => 'lifestyle',
    ]);

    Sanctum::actingAs($admin);

    $response = $this->patchJson("/api/v1/admin/categories/{$category->id}", [
        'name' => 'Healthy Lifestyle',
        'slug' => '',
    ]);

    $response->assertOk();

    $category->refresh();
    expect($category->name)->toBe('Healthy Lifestyle');
    expect($category->slug)->toBe('healthy-lifestyle');
});

it('deletes a category', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $category = Category::factory()->create();

    Sanctum::actingAs($admin);

    $response = $this->deleteJson("/api/v1/admin/categories/{$category->id}");

    $response->assertOk();
    $response->assertJson(['message' => 'Category deleted.']);

    $this->assertDatabaseMissing('category', ['id' => $category->id]);
});
