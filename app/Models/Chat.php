<?php

namespace App\Models;

use App\Traits\Helper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Chat extends Model
{
    use Helper;
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'message',
        'media_path',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id', 'id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function getMediaPathAttribute() : ?string
    {
        if ($this->attributes['media_path']) {
            return $this->getImageUrl($this->attributes['media_path']);
            return Storage::url($this->attributes['media_path']);
        }
        return null;
    }
}
