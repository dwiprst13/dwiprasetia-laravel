<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'content',
        'excerpt',
        'featured_image',
        'featured_image_alt',
        'category_slug',
        'tags',
        'status',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function savedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'saved_posts')->withPivot('created_at');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_slug', 'slug');
    }
}
