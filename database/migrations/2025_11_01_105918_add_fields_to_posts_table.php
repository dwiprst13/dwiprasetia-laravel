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
        Schema::table('posts', function (Blueprint $table) {
            $table->string('excerpt', 500)->nullable()->after('content');
            $table->string('featured_image_alt', 150)->nullable()->after('featured_image');
            $table->string('category_slug', 100)->nullable()->after('featured_image_alt');
            $table->json('tags')->nullable()->after('category_slug');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn([
                'excerpt',
                'featured_image_alt',
                'category_slug',
                'tags',
            ]);
        });
    }

};
