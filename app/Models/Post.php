<?php

namespace App\Models;

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

    // public function createdBy(): Attribute
    // {
    //     return new Attribute(set: fn() => auth()->user()->id);
    // }

    public function formattedPublishedAt(): Attribute
    {
        return new Attribute(get: fn() => $this->published_at ? date_create($this->published_at)->format('d/m/Y') : null);

        // return $this->published_at ? date_create($this->published_at)->format('d/m/Y') : null;
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at');
    }
}
