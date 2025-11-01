<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        $title = $this->faker->sentence(6);

        return [
            'user_id' => User::factory(),
            'title' => $title,
            'slug' => Str::slug($title) . '-' . $this->faker->unique()->numerify('###'),
            'content' => $this->faker->paragraphs(3, true),
            'excerpt' => $this->faker->optional()->text(200),
            'featured_image' => null,
            'featured_image_alt' => $this->faker->optional()->sentence(3),
            'category_slug' => null,
            'tags' => [],
            'status' => $this->faker->randomElement(['draft', 'published']),
        ];
    }
}
