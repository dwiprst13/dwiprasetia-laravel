<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Media>
 */
class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        $extension = $this->faker->fileExtension();
        $filename = Str::slug($this->faker->words(3, true));

        return [
            'user_id' => User::factory(),
            'disk' => 'public',
            'path' => "media/{$this->faker->uuid}.{$extension}",
            'original_name' => "{$filename}.{$extension}",
            'mime_type' => $this->faker->mimeType(),
            'size' => $this->faker->numberBetween(10_000, 2_000_000),
            'extension' => $extension,
            'alt_text' => $this->faker->sentence(3),
            'caption' => $this->faker->sentence(),
        ];
    }
}
