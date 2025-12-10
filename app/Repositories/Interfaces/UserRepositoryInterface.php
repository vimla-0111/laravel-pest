<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    public function getNonConversationalUser($userIds, $currentUserId): Collection;
    public function getConversationsUser(int $currentUserId, array $conversationIds, ?string $searchedValue): Collection;
}
