<?php

use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

it('lists media with filters', function () {
    Storage::fake('public');

    $admin = User::factory()->create(['role' => 'admin']);
    $matched = Media::factory()->for($admin, 'uploader')->create([
        'original_name' => 'sunset-photo.jpg',
        'caption' => 'Golden Sunset',
        'mime_type' => 'image/jpeg',
    ]);
    $other = Media::factory()->create([
        'original_name' => 'document.pdf',
        'caption' => 'Quarterly Report',
        'mime_type' => 'application/pdf',
    ]);

    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/v1/admin/media?search=sunset&mime=image&user_id=' . $admin->id);

    $response->assertOk();

    $data = $response->json('data');
    expect($data)->toBeArray();
    expect($data)->toHaveCount(1);
    $ids = collect($data)->pluck('id');
    expect($ids)->toContain($matched->id);
    expect($ids)->not->toContain($other->id);
});

it('allows admin to upload media files', function () {
    Storage::fake('public');

    $admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($admin);

    $response = $this->postJson('/api/v1/admin/media', [
        'file' => UploadedFile::fake()->image('banner.jpg', 1200, 600),
        'alt_text' => 'Homepage banner',
        'caption' => 'Hero section banner',
    ]);

    $response->assertCreated();

    $media = Media::first();
    expect($media)->not->toBeNull();
    expect($media->user_id)->toBe($admin->id);
    expect($media->original_name)->toBe('banner.jpg');
    expect($media->alt_text)->toBe('Homepage banner');
    expect($media->caption)->toBe('Hero section banner');

    Storage::disk('public')->assertExists($media->path);
});

it('replaces media files and metadata on update', function () {
    Storage::fake('public');

    $admin = User::factory()->create(['role' => 'admin']);
    $media = Media::factory()->for($admin, 'uploader')->create([
        'disk' => 'public',
        'path' => 'media/original.jpg',
        'original_name' => 'original.jpg',
        'mime_type' => 'image/jpeg',
        'extension' => 'jpg',
        'alt_text' => 'Original alt',
        'caption' => 'Original caption',
    ]);

    Storage::disk('public')->put($media->path, 'existing-content');

    Sanctum::actingAs($admin);

    $response = $this->putJson("/api/v1/admin/media/{$media->id}", [
        'file' => UploadedFile::fake()->image('updated.png', 800, 800),
        'alt_text' => 'Updated alt',
        'caption' => 'Updated caption',
    ]);

    $response->assertOk();

    $media->refresh();

    expect($media->original_name)->toBe('updated.png');
    expect($media->mime_type)->toBe('image/png');
    expect($media->extension)->toBe('png');
    expect($media->alt_text)->toBe('Updated alt');
    expect($media->caption)->toBe('Updated caption');

    Storage::disk('public')->assertMissing('media/original.jpg');
    Storage::disk('public')->assertExists($media->path);
});

it('deletes media and removes stored file', function () {
    Storage::fake('public');

    $admin = User::factory()->create(['role' => 'admin']);
    $media = Media::factory()->for($admin, 'uploader')->create([
        'disk' => 'public',
        'path' => 'media/to-delete.jpg',
    ]);

    Storage::disk('public')->put($media->path, 'to-delete');

    Sanctum::actingAs($admin);

    $response = $this->deleteJson("/api/v1/admin/media/{$media->id}");

    $response->assertOk();
    $response->assertJson([
        'message' => 'Media deleted.',
    ]);

    $this->assertDatabaseMissing('media', ['id' => $media->id]);
    Storage::disk('public')->assertMissing('media/to-delete.jpg');
});
