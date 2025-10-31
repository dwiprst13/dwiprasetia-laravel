<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content');
            $table->string('featured_image', 255)->nullable();
            $table->string('thumbnail', 255)->nullable();
            $table->string('og_image', 255)->nullable();
            $table->unsignedSmallInteger('reading_time')->nullable();
            $table->string('meta_title', 255)->nullable();
            $table->text('meta_description')->nullable();
            $table->string('canonical_url', 255)->nullable();
            $table->enum('status', ['draft', 'scheduled', 'published', 'archived'])->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->unsignedBigInteger('view_count')->default(0);
            $table->unsignedBigInteger('like_count')->default(0);
            $table->unsignedBigInteger('comment_count')->default(0);
            $table->boolean('allow_comments')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
