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
}
