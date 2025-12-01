<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    protected $fillable = ['type'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_users');
    }

    public function getReceiverAttribute() : Collection
    {
        // Return the first user who is NOT the logged-in user
        return $this->users->where('id', '!=', auth()->id());
    }

    public function hasUser($user): bool
    {
        return $this->users()->where('users.id', $user->id)->exists();
    }

    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class, 'conversation_id');
    }

    // This fetches the single latest message efficiently
    public function latestMessage() : HasOne
    {
        return $this->hasOne(Chat::class, 'conversation_id')->latestOfMany();
    }
}
