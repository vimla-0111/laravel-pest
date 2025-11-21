<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

use function Laravel\Prompts\info;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Message $message)
    {
        info('MessageSent Event Created');
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('chat'), // Using a public channel for this simple demo
            new Channel('public'), // Using a public channel for this simple demo
            new PrivateChannel('private_chat')
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    // Data sent to the browser
    public function broadcastWith(): array
    {
        Log::info($this->message);
        return [
            'id' => $this->message->id,
            'text' => $this->message->text,
            'user_name' => $this->message->user->name,
            'user_id' => $this->message->user_id,
        ];
    }
}
