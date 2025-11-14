<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PostPolicy
{
    // Can the user update this post?
    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->created_by;
    }

    // Can the user delete this post?
    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->created_by;
        // return $user->id === $post->created_by
        //     ? Response::allow()
        //     : Response::deny('You do not own this post.');
    }
}
