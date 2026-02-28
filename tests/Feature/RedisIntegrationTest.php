<?php

use App\Repositories\ChatRepository;
use App\Jobs\NotifyUserOfNewPost;
use App\Models\Conversation;
use App\Models\Chat;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;

it('caches paginated posts for the current user and flushes on change', function () {
    Config::set('cache.default', 'redis');
    Cache::store('redis')->flush();

    $user = User::factory()->create();
    Post::factory()->count(3)->for($user, 'creator')->create();

    actingAs($user);

    // populate cache
    $this->get('/posts')->assertStatus(200);

    $cacheKey = 'page:1';
    expect(Cache::store('redis')->tags(['posts', "user:{$user->id}"])->has($cacheKey))->toBeTrue();

    // create another post which should flush cache
    Post::factory()->for($user, 'creator')->create();

    expect(Cache::store('redis')->tags(['posts', "user:{$user->id}"])->has($cacheKey))->toBeFalse();
});

it('caches chat stats and invalidates when chats change', function () {
    Config::set('cache.default', 'redis');
    Cache::store('redis')->flush();

    $conversation = Conversation::create(['type' => 'private']);
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $conversation->users()->attach([$user1->id, $user2->id]);

    $repo = app(ChatRepository::class);

    $stats = $repo->getChatStats((string) $conversation->id);
    expect($stats['unReadCount'])->toBe(0);
    expect(Cache::store('redis')->has("chat_stats:{$conversation->id}"))->toBeTrue();

    // when a new chat is created the cached data should be flushed
    $chat = $repo->createChat($conversation, (object) ['user' => $user1, 'body' => 'hey'], null);
    $this->assertDatabaseHas('chats', ['id' => $chat->id]);

    $stats2 = $repo->getChatStats((string) $conversation->id);
    expect($stats2['unReadCount'])->toBe(1);
});

it('dispatches jobs with redis connection when queue.default is redis', function () {
    Config::set('queue.default', 'redis');

    Queue::fake();

    $user = User::factory()->create();
    $post = Post::factory()->for($user, 'creator')->create();

    NotifyUserOfNewPost::dispatch($post, $user);

    Queue::assertPushed(NotifyUserOfNewPost::class, function ($job) {
        return $job->connection === 'redis';
    });
});
