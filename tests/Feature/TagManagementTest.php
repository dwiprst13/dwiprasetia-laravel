<?php

use App\Models\Tag;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('lists tags with search filters', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $matched = Tag::factory()->create(['name' => 'Laravel', 'slug' => 'laravel']);
    $other = Tag::factory()->create(['name' => 'VueJS', 'slug' => 'vuejs']);

    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/v1/admin/tags?search=laravel');

    $response->assertOk();

    $data = $response->json('data');
    expect($data)->toBeArray();
    $ids = collect($data)->pluck('id');
    expect($ids)->toContain($matched->id);
    expect($ids)->not->toContain($other->id);
});

it('creates a new tag and normalizes slug', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($admin);

    $response = $this->postJson('/api/v1/admin/tags', [
        'name' => 'PHP Tips',
        'slug' => 'PHP Tips',
        'description' => 'Short PHP snippets.',
    ]);

    $response->assertCreated();

    $this->assertDatabaseHas('tags', [
        'name' => 'PHP Tips',
        'slug' => 'php-tips',
    ]);
});

it('updates a tag and regenerates slug when needed', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $tag = Tag::factory()->create([
        'name' => 'Backend',
        'slug' => 'backend',
    ]);

    Sanctum::actingAs($admin);

    $response = $this->patchJson("/api/v1/admin/tags/{$tag->id}", [
        'name' => 'Backend Dev',
        'slug' => '',
    ]);

    $response->assertOk();

    $tag->refresh();
    expect($tag->name)->toBe('Backend Dev');
    expect($tag->slug)->toBe('backend-dev');
});

it('deletes a tag', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $tag = Tag::factory()->create();

    Sanctum::actingAs($admin);

    $response = $this->deleteJson("/api/v1/admin/tags/{$tag->id}");

    $response->assertOk();
    $response->assertJson(['message' => 'Tag deleted.']);

    $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
});
