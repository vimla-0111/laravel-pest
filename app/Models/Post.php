<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'created_by',
        'published_at',
        'status'
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function formattedPublishedAt(): Attribute
    {
        return new Attribute(get: fn() => $this->published_at ? date_create($this->published_at)->format('d/m/Y') : null);
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
