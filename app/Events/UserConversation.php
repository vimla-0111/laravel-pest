<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use phpDocumentor\Reflection\Types\This;

use function Laravel\Prompts\info;

class UserConversation implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(protected string $userId, protected array $data)
    {
        info('update conversation');
        info(collect($this->data));
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('global_chat'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'users.converastion.update';
    }

    public function broadcastWith(): array
    {
        $user = User::find($this->userId);
        return [
            'id' => $user?->id,
            'name' => $user?->name,
            'unread_message_count' => $this->data['unReadCount'],
            'last_message' => $this->data['latestMessage']
        ];
    }
}
