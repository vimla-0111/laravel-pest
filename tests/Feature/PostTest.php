<?php

use App\Models\Post;
use App\Models\User;
use Tests\TestCase; // Correct base class

// uses(TestCase::class); // If using Pest's `uses()` function

function fakeUser()     // common helper fn for this class only
{
    return User::factory()->create();
}

function fakePost()
{
    return Post::factory()->create();
}

function actAsCustomer(): TestCase
{
    $user = fakeUser();
    return test()->actingAs($user);
}

// beforeEach(fn() => Post::factory()->create());          // Hooks example

test('example', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

describe('post\'s crud', function () {
    it('get the 10 post record', function () {
        // Post::factory()->count(10)->create();
        fakePost();

        $response = actAsCustomer()->get(route('posts.index'));

        $response->assertStatus(200)
            ->assertViewIs('posts.index');

        // Assert 2: View Data
        $response->assertViewHas('posts'); // Check if 'posts' variable is passed
    });

    // higher order testing example
    // Note: url using route helper not working ex: get(route('posts.create'))
    it('return the create post page')->actAsCustomer()->get('/posts/create')->assertStatus(200)->assertViewIs('posts.create');

    it('store the post by authenticate user', function () {
        $post = fakePost();

        $postRequest = [
            'title' => 'test',
            'content' => 'test',
        ];
        $response = actAsCustomer()->post(route('posts.store'), $postRequest);

        $response->assertStatus(302)
            ->assertRedirect(route('posts.index'))
            ->assertSessionHas('success');
        // ->assertDatabaseHas('posts', [
        //     'id' => $post->id
        // ]);
    });

    it('unable to store the post by unauthenticate user', function () {
        // Post::factory()->create();
        fakePost();

        $postRequest = [
            'title' => 'test',
            'content' => 'test',
        ];
        $response = $this->post(route('posts.store'), $postRequest);

        $response->assertStatus(302)
            ->assertRedirect(route('login'));
    });

    it('unable to store the post without title and content', function (?string $title, ?string $content) {
        // Post::factory()->create();
        fakePost();

        $postRequest = [
            'title' => $title,
            'content' => $content,
        ];
        $response = actAsCustomer()->from(route('posts.create'))->post(route('posts.store'), $postRequest);

        $response->assertStatus(302)
            ->assertSessionHasErrors([(!$title  ? 'title' : null), (!$content ? 'content' : null)])
            ->assertRedirect(route('posts.create'));
    })->with([
        [null, 'test content'],
        ['test title', null]
    ]); // dataset example

    it('return the edit page with post detail', function () {
        $post = fakePost();

        $response = actAsCustomer()->get(route("posts.edit", $post->id));

        $response->assertStatus(200)
            ->assertViewIs('posts.edit')
            ->assertViewHas('post');
    });

    it('update the post', function () {
        $post = fakePost();

        $response = actAsCustomer()->put(route("posts.update", $post->id));
    });
});
