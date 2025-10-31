<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'thumbnail',
        'og_image',
        'reading_time',
        'meta_title',
        'meta_description',
        'canonical_url',
        'status',
        'published_at',
        'scheduled_at',
        'view_count',
        'like_count',
        'comment_count',
        'allow_comments',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'allow_comments' => 'boolean',
        'view_count' => 'integer',
        'like_count' => 'integer',
        'comment_count' => 'integer',
        'reading_time' => 'integer',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
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

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function refreshEngagementCounts(): void
    {
        $this->forceFill([
            'like_count' => $this->likes()->count(),
            'comment_count' => $this->comments()->count(),
        ])->saveQuietly();
    }

    public function incrementViewCounter(): void
    {
        $this->increment('view_count');
    }
}
