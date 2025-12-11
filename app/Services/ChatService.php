<?php

namespace App\Services;

use App\Events\ChatDeleted;
use App\Events\ChatRead;
use App\Events\SentPrivateMessage;
use App\Events\UserConversation;
use App\Models\Conversation;
use App\Models\User;
use App\Repositories\Interfaces\ChatRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Traits\Helper;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
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

    public function getUsersList(int $currrentUserId, mixed $searchedValue = null): Collection
    {
        $conversationIds = $this->repository->getConversationIdsOfCurrentUser($currrentUserId);
        $users = $this->userRepository->getConversationsUser($currrentUserId, $conversationIds->toArray(), $searchedValue)->map(function ($user) {
            $convo = $user->conversations->first();
            $user->last_message = $convo?->latestMessage?->media_path ? 'media' : $convo?->latestMessage?->message ?? null;
            $user->date = $convo?->latestMessage?->created_at;
            return $user;
        });

        $users = $users->sortByDesc('date');
        $users = $users->values();

        return $users;
    }

    public function createConversationIfNotExists($currrentUserId, $recipientId): Conversation
    {
        $conversation = $this->repository->findConversationByUsers($currrentUserId, $recipientId);

        if (! $conversation) {
            $conversation =  $this->repository->createUsersConversation($currrentUserId, $recipientId);
        }

        return $conversation;
    }

    public function getConversationMessages(int $conversationId): LengthAwarePaginator
    {
        $conversation = $this->repository->getConversationById($conversationId);
        $messages =  $this->repository->getConversationMessagesByPagination($conversation);
        $chronologicalMessages = $messages->getCollection()->reverse()->values();

        $messages->setCollection($chronologicalMessages);
        return  $messages;
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
            // dd($th);
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

    public function deleteChats(int $conversationId, array $chatIds): void
    {
        Log::info('delete chat event start');
        broadcast(new ChatDeleted($chatIds, $conversationId))->toOthers();

        try {
            DB::transaction(function () use ($conversationId, $chatIds) {
                $this->deleteChatsWithMedia($conversationId, $chatIds);
            });
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
