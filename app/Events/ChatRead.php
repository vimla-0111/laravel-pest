<?php

namespace App\Events;

use App\Models\Chat;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatRead implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Chat $chat)
    {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->chat->conversation_id),

        ];
    }

    public function broadcastAs(): string
    {
        return 'chat.message.read';
    }

    // Data sent to the browser
    public function broadcastWith(): array
    {
        return [
            'id' => $this->chat->id,
            'sender_id' => $this->chat->sender_id,
            'receivers'   => $this->chat->conversation->receiver->pluck('id'),
            'message' => $this->chat->message,
            'read_at' => $this->chat->read_at,
        ];
    }
}
