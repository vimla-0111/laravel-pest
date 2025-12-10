<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class UserRepository implements UserRepositoryInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function getNonConversationalUser($userIds, $currentUserId): Collection
    {
        return  User::isCustomer()
            ->whereNot('id', $currentUserId)
            ->whereNotIn('id', $userIds)
            ->get(['id', 'name']);
    }

    public function getConversationsUser( int $currentUserId, array $conversationIds, ?string $searchedValue) :Collection
    {
        return  User::isCustomer()
            ->whereLike('name', '%' . $searchedValue . '%')
            ->whereNot('id', $currentUserId)
            ->withWhereHas(
                'conversations',
                function ($q) use ($conversationIds) {
                    return $q->whereIn('conversations.id', $conversationIds)
                        ->with('latestMessage');
                }
            )
            ->withCount(['chats as unread_message_count' => function ($q) use ($conversationIds, $currentUserId) {
                return $q->where('sender_id', '!=', $currentUserId)->whereIn('conversation_id', $conversationIds)->whereNull('read_at');
            }])
            ->get();
    }
}
