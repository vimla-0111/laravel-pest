<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = ['type'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_users');
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
    public function latestMessage()
    {
        return $this->hasOne(Chat::class,'conversation_id')->latestOfMany();
    }
}
