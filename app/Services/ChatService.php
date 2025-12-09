<?php

namespace App\Services;

use App\Events\ChatRead;
use App\Events\SentPrivateMessage;
use App\Events\UserConversation;
use App\Exceptions\ChatException;
use App\Models\Chat;
use App\Models\Conversation;
use App\Models\User;
use App\Repositories\Interfaces\ChatRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Traits\Helper;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChatService
{
    use Helper;
    /**
     * Create a new class instance.
     */
    public function __construct(protected ChatRepositoryInterface $repository, protected UserRepositoryInterface $userRepository)
    {
        //
    }

    public function getConversationMeta(string $conversationId): array
    {
        $stats = $this->repository->getChatStats($conversationId);
        return $stats;
    }

    public function getUsersList(User $user, mixed $searchedValue = null): Collection
    {
        $conversationIds = $user->conversations()->pluck('conversations.id');

        $users = User::isCustomer()
            ->whereLike('name', '%' . $searchedValue . '%')
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

    public function createConversationIfNotExists($currrentUser, $recipientId): Conversation
    {

        $conversation = Conversation::whereHas('users', function ($q) use ($currrentUser) {
            $q->where('users.id', $currrentUser->id);
        })->whereHas('users', function ($q) use ($recipientId) {
            $q->where('users.id', $recipientId);
        })->with('latestMessage')
            ->first();

        if (! $conversation) {
            $conversation = Conversation::create(['type' => 'private']);
            $conversation->users()->attach([$currrentUser->id, $recipientId]);
            $conversation->load('latestMessage');
        }

        return $conversation;
    }

    public function getConversationMessages(int $conversationId): Collection
    {
        $conversation = $this->repository->getConversationById($conversationId);

        return $this->repository->getConversationMessages($conversation);
    }

    public function sendMessage(Request $chatRequest, $conversationId)
    {
        try {
            $path = null;
            if ($chatRequest->image) {
                $path = $this->storeMedia($chatRequest->image);
            }

            $conversation = $this->repository->getConversationById($conversationId);

            DB::beginTransaction();
            $chat = $this->repository->createChat($conversation, $chatRequest, $path);
            DB::commit();

            $chat->load('sender');

            // Broadcast the message to the conversation channel
            broadcast(new SentPrivateMessage($chat));

            $data = $this->getConversationMeta($conversationId);
            broadcast(new UserConversation($chatRequest->selectedUserId, $data));

            return $chat;
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->deleteMedia($path);
            throw $th;
        }
    }

    public function markChatAsRead(Request $chatRequest): void
    {
        foreach ($chatRequest['messages'] as $message) {
            $chat = $this->repository->getChatById($message['messageId']);
            Log::info('Marking chat as read: ' . $chat->id . ' at ' . $message['read_at']);
            if ($chat) {
                $this->repository->updateChatReadAt($chat, $message['read_at']);
                broadcast(new ChatRead($chat))->toOthers();
            }
        }
    }

    public function getUserForNewConversation($currrentUserId): Collection
    {
        $userIds = $this->repository->getNonConversationalUserIds($currrentUserId, $this->repository->getConversationIdsOfCurrentUser($currrentUserId));

        $users = $this->userRepository->getNonConversationalUser($userIds, $currrentUserId);
        return $users;
    }

    public function deleteChatsWithMedia(int $conversationId, array $chatIds)
    {
        $this->repository->chunkChatsForDeletion(
            $conversationId,
            $chatIds,
            function ($chats) {
                foreach ($chats as $chat) {
                    if ($chat->media_path) {
                        $originalPath = $chat->getRawOriginal('media_path');
                        Log::info('Deleting media file: ' . $originalPath);
                        $this->deleteMediaFromStorage($originalPath);
                        $this->deleteMediaFromStorage($originalPath . '.meta');
                    }
                    $chat->delete();
                }
            }
        );
    }
}
