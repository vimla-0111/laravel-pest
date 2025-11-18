<?php

namespace App\Services;

use App\Jobs\NotifyUserOfNewPost;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Str;

use function Symfony\Component\Clock\now;

class PostService
{
    /**
     * Create a new post and handle related tasks.
     *
     * @param User $user The user creating the post.
     * @param array $data The validated data from the request.
     * @return Post The newly created post.
     */
    public function createPost(User $user, array $data): Post
    {
        // 1. Create the post and associate it with the user
        $post = $user->posts()->create([
            'title' => $data['title'],
            'content' => $data['content'],
            'published_at' => now() ?? null,
        ]);

        // 2. Dispatch a job to the queue
        // We do this so the user doesn't have to wait for the email to send
        NotifyUserOfNewPost::dispatch($post, $user);

        // 3. Return the created post to the controller
        return $post;
    }
}
