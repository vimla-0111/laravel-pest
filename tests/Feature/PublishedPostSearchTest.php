<?php

use App\Http\Controllers\PublishedPostSearchController;
use App\Models\Post;
use Illuminate\Support\Facades\Config;

covers(PublishedPostSearchController::class);

beforeEach(function () {
    Config::set('scout.driver', 'collection');
});

test('authenticated user can open the published posts discovery page', function () {
    $user = fakeUser();

    $response = $this->actingAs($user)->get(route('posts.discover'));

    $response->assertOk()
        ->assertViewIs('posts.discover');
});

test('guest is redirected from the published posts discovery page', function () {
    $response = $this->get(route('posts.discover'));

    expect($response)->redirectToLogin();
});

test('blank search shows published posts ordered by latest published date', function () {
    $user = fakeUser();

    Post::factory()->create([
        'title' => 'Hidden Draft',
        'content' => 'This should not be visible.',
        'published_at' => null,
    ]);

    Post::factory()->create([
        'title' => 'Older Published Post',
        'content' => 'Older published content',
        'published_at' => now()->subDays(2),
    ]);

    Post::factory()->create([
        'title' => 'Newest Published Post',
        'content' => 'Newest published content',
        'published_at' => now()->subDay(),
    ]);

    $response = $this->actingAs($user)->get(route('posts.discover'));

    $response->assertOk()
        ->assertSeeInOrder(['Newest Published Post', 'Older Published Post'])
        ->assertDontSee('Hidden Draft');
});

test('search returns published posts that match the title', function () {
    $user = fakeUser();

    Post::factory()->create([
        'title' => 'Laravel Scout Search',
        'content' => 'A post about Scout',
        'published_at' => now(),
    ]);

    Post::factory()->create([
        'title' => 'Different Title',
        'content' => 'Nothing to match here',
        'published_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('posts.discover', ['search' => 'Scout']));

    $response->assertOk()
        ->assertSee('Laravel Scout Search')
        ->assertDontSee('Different Title');
});

test('search returns published posts that match the content', function () {
    $user = fakeUser();

    Post::factory()->create([
        'title' => 'Alpha Post',
        'content' => 'Typesense makes this search fast',
        'published_at' => now(),
    ]);

    Post::factory()->create([
        'title' => 'Beta Post',
        'content' => 'No matching text here',
        'published_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('posts.discover', ['search' => 'Typesense']));

    $response->assertOk()
        ->assertSee('Alpha Post')
        ->assertDontSee('Beta Post');
});

test('unpublished posts are excluded from search results', function () {
    $user = fakeUser();

    Post::factory()->create([
        'title' => 'Visible Search Result',
        'content' => 'Published Scout content',
        'published_at' => now(),
    ]);

    Post::factory()->create([
        'title' => 'Hidden Search Result',
        'content' => 'Published Scout content',
        'published_at' => null,
    ]);

    $response = $this->actingAs($user)->get(route('posts.discover', ['search' => 'Scout']));

    $response->assertOk()
        ->assertSee('Visible Search Result')
        ->assertDontSee('Hidden Search Result');
});

test('search pagination keeps the search query string', function () {
    $user = fakeUser();

    Post::factory()->count(11)->sequence(
        fn ($sequence) => [
            'title' => 'Searchable Post '.$sequence->index,
            'content' => 'Laravel Scout pagination content',
            'published_at' => now()->subMinutes($sequence->index),
        ]
    )->create();

    $response = $this->actingAs($user)->get(route('posts.discover', ['search' => 'Searchable']));

    $response->assertOk()
        ->assertSee('search=Searchable', false);
});

test('existing posts index still shows only the authenticated users own posts', function () {
    $user = fakeUser();
    $otherUser = fakeUser();

    Post::factory()->create([
        'title' => 'My Managed Post',
        'created_by' => $user->id,
    ]);

    Post::factory()->create([
        'title' => 'Someone Else Post',
        'created_by' => $otherUser->id,
    ]);

    $response = $this->actingAs($user)->get(route('posts.index'));

    $response->assertOk()
        ->assertSee('My Managed Post')
        ->assertDontSee('Someone Else Post');
});
