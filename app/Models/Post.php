<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;

class Post extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'title',
        'content',
        'created_by',
        'published_at',
        'status',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function formattedPublishedAt(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->published_at?->format('d/m/Y')
        );
    }

    public function scopePublished($query): Builder
    {
        return $query->whereNotNull('published_at');
    }

    public function scopeCreatedBy($query, $id): Builder
    {
        return $query->where('created_by', $id);
    }

    /**
     * Clear any cached post listings for a given user.
     *
     * We cache paginated results using Redis tags "posts" and "user:{id}".
     * This helper is convenient for controllers or services to call after
     * changing a user's collection of posts.
     */
    public static function flushCacheForUser(int $userId): void
    {
        cache()->tags(['posts', "user:{$userId}"])->flush();
    }

    public function searchableAs(): string
    {
        return 'posts';
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => (string) $this->id,
            'title' => (string) $this->title,
            'content' => (string) $this->content,
            'created_by' => (int) $this->created_by,
            'published_at' => $this->published_at?->timestamp,
            'created_at' => $this->created_at?->timestamp ?? now()->timestamp,
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return $this->published_at !== null;
    }

    protected function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->published();
    }

    /**
     * Retrieve the model for a bound value.
     *
     * @param  mixed  $value
     * @param  string|null  $field
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    // public function resolveRouteBinding($value, $field = null)
    // {
    //     // Example: Find by slug and ensure it's published
    //     return $this->published()->findOrFail($value);
    // }
}
