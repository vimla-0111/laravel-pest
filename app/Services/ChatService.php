<?php

namespace App\Services;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ChatService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function getUpdatedUserConversation(string $conversationId): array
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

    public function getUsersList(User $user, mixed $searchedValue = null): Collection
    {
        $conversationIds = $user->conversations()->pluck('conversations.id');

        $users = User::isCustomer()
            ->whereLike('name', '%'.$searchedValue.'%')
            ->whereNot('id', auth()->user()->id)
            ->withWhereHas(
                'conversations',
                function ($q) use ($conversationIds) {
                    return $q->whereIn('conversations.id', $conversationIds)
                        ->with('latestMessage');
                }
            )
            ->withCount(['chats as unread_message_count' => function ($q) use ($conversationIds) {
                return $q->where('sender_id', '!=', auth()->id())->whereIn('conversation_id', $conversationIds)->whereNull('read_at');
            }])
            // ->select(['id','name','unread_message_count'])
            ->get(['id', 'name', 'unread_message_count'])
            ->map(function ($user) {
                $convo = $user->conversations->first();
                $user->last_message = $convo?->latestMessage?->media_path ? 'media' : $convo?->latestMessage?->message ?? null;
                $user->date = $convo?->latestMessage?->created_at;

                // $user->unread_message_count = 0;
                return $user;
            });

        $users = $users->sortByDesc('date');
        $users = $users->values();

        return $users;
    }
}
