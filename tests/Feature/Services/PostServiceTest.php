<?php

use App\Jobs\NotifyUserOfNewPost;
use App\Mail\UserNewPostNotification;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;


beforeEach(function () {
    $this->postService = $this->app->make(PostService::class);
});

test('createPost()', function () {
    Queue::fake();

    $user = fakeUser();

    $post = $this->postService->createPost($user, ['title' => 'New Title', 'content' => 'New Content']);

    $this->assertDatabaseHas('posts', ['created_by' => $user->id]);

    Queue::assertPushed(NotifyUserOfNewPost::class);

    // You can also check that it was pushed with the correct data
    Queue::assertPushed(NotifyUserOfNewPost::class, function ($job) use ($post) {
        // Check that the job instance has the correct post
        return $job->post->id === $post->id;
    });
});

it('sends a notification email', function () {
    Mail::fake();
    $post = Post::factory()->create();
    $user = fakeUser();

    (new NotifyUserOfNewPost($post, $user))->handle();

    Mail::assertSent(UserNewPostNotification::class);

    // You can also check *who* it was sent to
    Mail::assertSent(UserNewPostNotification::class, function ($mail) use ($user, $post) {
        // Check that it was sent to the correct admin
        return $mail->hasTo($user->email) &&
            // Check that the mailable has the correct post data
            $mail->post->id === $post->id;
    });
});


