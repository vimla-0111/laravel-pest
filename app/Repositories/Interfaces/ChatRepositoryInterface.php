<?php

namespace App\Repositories\Interfaces;

use App\Models\Chat;
use App\Models\Conversation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;

interface ChatRepositoryInterface
{
    public function getConversationById(int $id): Conversation;

    public function getConversationMessages(Conversation $conversation): Collection;

    public function getChatStats(string $conversationId): array;

    public function createChat(Conversation $conversation, $chatRequest, ?string $path): Chat;

    public function getChatById(int $id): Chat;

    public function updateChatReadAt(Chat $chat, string $readAt): void;

    public function getConversationIdsOfCurrentUser(int $currentUserId): SupportCollection;

    public function getNonConversationalUserIds(int $currentUserId, SupportCollection $conversationIds): SupportCollection;

    public function chunkChatsForDeletion(int $conversationId, array $ids, callable $callback): void;

    public function findConversationByUsers(int $currrentUserId, int $recipientId): Conversation;

    public function createUsersConversation(int $currrentUserId, int $recipientId): Conversation;

        public function getConversationMessagesByPagination(Conversation $conversation): LengthAwarePaginator;

    // public function storeMessage(MessageDTO $data);
}
