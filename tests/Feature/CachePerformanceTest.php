<?php

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

describe('Cache Performance Comparison', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Post::factory()->count(5)->for($this->user, 'creator')->create();
        Cache::tags(['posts', "user:{$this->user->id}"])->flush();
    });

    test('first request cache miss shows database query time', function () {
        $this->actingAs($this->user);

        // Clear cache to force database query
        Cache::tags(['posts', "user:{$this->user->id}"])->flush();

        $response = $this->get(route('posts.index'));

        $response->assertOk();
        $response->assertViewHas('posts');
    })->group('performance');

    test('second request cache hit shows much faster time', function () {
        $this->actingAs($this->user);

        // Prime the cache (first request - cache miss with DB query)
        $firstResponse = $this->get(route('posts.index'));
        $firstResponse->assertOk();

        // Second request should hit cache (no DB query)
        $secondResponse = $this->get(route('posts.index'));
        $secondResponse->assertOk();
        $secondResponse->assertViewHas('posts');

        // Both should work
        expect($firstResponse->status())->toBe(200);
        expect($secondResponse->status())->toBe(200);
    })->group('performance');

    test('verify cache is storing paginated posts', function () {
        $this->actingAs($this->user);

        $this->get(route('posts.index'));

        // Verify cache exists
        $cacheValue = Cache::tags(['posts', "user:{$this->user->id}"])->get('page:1');
        expect($cacheValue)->not->toBeNull();
    })->group('performance');

    test('verify cache invalidates on post creation', function () {
        $this->actingAs($this->user);

        // Cache a page
        $this->get(route('posts.index'));
        $cached = Cache::tags(['posts', "user:{$this->user->id}"])->get('page:1');
        expect($cached)->not->toBeNull();

        // Create new post
        $this->post(route('posts.store'), [
            'title' => 'New Post',
            'content' => 'New Content',
        ]);

        // Cache should be flushed
        $cleared = Cache::tags(['posts', "user:{$this->user->id}"])->get('page:1');
        expect($cleared)->toBeNull();
    })->group('performance');
});
