<?php

namespace App\Repositories;

use App\Exceptions\ChatException;
use App\Models\Chat;
use App\Models\Conversation;
use App\Models\ConversationUser;
use App\Models\User;
use App\Repositories\Interfaces\ChatRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Log;

class ChatRepository implements ChatRepositoryInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function getConversationById(int $id): Conversation
    {
        $conversation = Conversation::find($id);
        throw_if(!$conversation, new ChatException('conversation not found.'));
        return $conversation;
    }

    public function getChatById(int $id): Chat
    {
        $chat = Chat::find($id);
        throw_if(!$chat, new ChatException('Chat not found.'));
        return $chat;
    }

    public function getConversationMessages(Conversation $conversation): Collection
    {
        return $conversation->chats()
            ->with('sender')
            ->get();
    }

    public function getChatStats(string $conversationId): array
    {

        $chat = Chat::where('conversation_id', $conversationId);

        if ($chat) {
            $unReadCount = $chat->whereNull('read_at')->count();
            $latestMessage = $chat->orderByDesc('created_at')->limit(1)->value('message');
        } else {
            $unReadCount = 0;
            $latestMessage = null;
        }

        $data = [
            'unReadCount' => $unReadCount,
            'latestMessage' => $latestMessage,
        ];

        return $data;
    }

    public function createChat(Conversation $conversation, $chatRequest, ?string $path): Chat
    {
        return $conversation->chats()->create([
            'sender_id' => $chatRequest->user()->id,
            'message' => $chatRequest->body,
            'media_path' => $path,
        ]);
    }

    public function updateChatReadAt(Chat $chat, string $readAt): void
    {
        $chat->read_at = Carbon::parse($readAt);
        $chat->save();
    }

    public function getConversationIdsOfCurrentUser(int $currentUserId): SupportCollection
    {
        return ConversationUser::where('user_id', $currentUserId)->pluck('conversation_id');
    }

    public function getNonConversationalUserIds(int $currentUserId, SupportCollection $conversationIds): SupportCollection
    {
        return ConversationUser::whereNot('user_id', $currentUserId)->whereIn('conversation_id', $conversationIds)->pluck('user_id');
    }

    public function chunkChatsForDeletion(int $conversationId, array $ids, callable $callback)
    {
        Chat::where('conversation_id', $conversationId)
            ->whereIn('id', $ids)
            ->chunkById(50, function ($chats) use ($callback) {
                $callback($chats);
            });
    }
}
