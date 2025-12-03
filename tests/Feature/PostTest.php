<?php

use App\Http\Controllers\PostController;
use App\Models\Post;
use App\Models\User;
use Tests\TestCase; // Correct base class
use Illuminate\Database\Eloquent\Collection;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

covers(PostController::class);          // mutation test

// function fakeUser(): User     // common helper fn for this class only
// {
//     return User::factory()->create();
// }

function fakePost(int $count = 1): Collection|Post
{
    return $count === 1
        ? Post::factory()->create()
        : Post::factory()->count($count)->create();
}

function actAsCustomer(?User $user = null): TestCase
{
    $user ??= fakeUser();
    return test()->actingAs($user);
}

// beforeEach(fn() => Post::factory()->create());          // Hooks example

test('redirect to dashboard', function () {
    $response = actAsCustomer()->get('/');
    $response->assertRedirect(route('dashboard'));
    // $response->assertStatus(200);
});


test('redirect to login due to unauthenticated', function () {
    $response =  $this->get('/');
    $response->assertRedirect(route('login'));
    // $response->assertStatus(200);
});

// describe('user can ', function () {
test('index() get the 10 post record', function () {
    fakePost(10);

    $response = actAsCustomer()->get(route('posts.index'));

    $response->assertStatus(200)
        ->assertViewIs('posts.index');

    // Assert 2: View Data
    $response->assertViewHas('posts'); // Check if 'posts' variable is passed
})->group('auth');

// higher order testing example
// Note: url using route helper not working ex: get(route('posts.create')), pass url string instead
test('create() show the create post page')->actAsCustomer()->get('/posts/create')->assertStatus(200)->assertViewIs('posts.create')->group('auth');

test('store() store the post', function () {

    $user = fakeUser();

    $postRequest = [
        'title' => 'test title',
        'content' => 'test',
    ];
    $response = actAsCustomer($user)->post(route('posts.store'), $postRequest);

    $response->assertStatus(302)
        ->assertRedirect(route('posts.index'))
        ->assertSessionHas('success', 'Post created successfully!');

    $this->assertDatabaseHas('posts', ['created_by' => $user->id]);
})->group('auth');

test('edit() show the edit page with post detail', function () {
    $post = fakePost();

    $response = actAsCustomer()->get(route("posts.edit", $post->id));

    $response->assertStatus(200)
        ->assertViewIs('posts.edit')
        ->assertViewHas('post');
})->group('auth');

test('update() update the post with permission', function () {
    $user = fakeUser();
    $post = Post::factory()->createdBy($user->id)->create();

    $postRequest = [
        'title' => 'Updated Title',
        'content' => 'Updated Content'
    ];

    $response = actAsCustomer($user)->put(route("posts.update", $post->id), $postRequest);

    $response->assertStatus(302)
        ->assertRedirect(route('posts.index'))
        ->assertSessionHas('success', 'Post updated successfully!');

    $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => $postRequest['title'], 'content' => $postRequest['content'], 'created_by' => $user->id]);

    // expect($post->fresh()->created_by)->toBe($user['id']);
    // expect($post->fresh()->title)->toBe($postRequest['title']);
    // expect($post->fresh()->content)->toBe($postRequest['content']);
})->group('authorized', 'auth');



test('destroy() delete the post with permission', function () {
    $user = fakeUser();
    $post = Post::factory()->createdBy($user->id)->create();

    $response = actAsCustomer($user)->delete(route("posts.destroy", $post->id));

    $this->assertDatabaseEmpty('posts');

    $response->assertStatus(302)
        ->assertRedirect(route('posts.index'))
        ->assertSessionHas('success', 'Post deleted successfully!');
})->group('authorized', 'auth');


test('failed to update the post without permission', function () {
    $user = fakeUser();
    $GuestUser = fakeUser();
    $post = Post::factory()->createdBy($user->id)->create();

    $postRequest = [
        'title' => 'Updated Title',
        'content' => 'Updated Content'
    ];

    $response = actAsCustomer($GuestUser)->put(route("posts.update", $post->id), $postRequest);

    $response->assertForbidden(403);
})->group('auth', 'unauthorized');

test('failed to delete the post without permission', function () {
    $user = fakeUser();
    $authUser = fakeUser();

    $post = Post::factory()->createdBy($authUser->id)->create();

    $response = actAsCustomer($user)->delete(route("posts.destroy", $post->id));

    $this->assertDatabaseCount('posts', 1);

    $response->assertForbidden(403);
})->group('auth', 'unauthorized');
// });



describe('guest user', function () {
    test('unable to get the 10 post record', function () {
        fakePost(10);

        $response = $this->actingAsGuest()->get(route('posts.index'));

        expect($response)->redirectToLogin();
    });

    test('unable to view the create post page', function () {
        $response = $this->get('/posts/create');
        expect($response)->redirectToLogin();
    });

    test('unable to store the post', function () {

        $postRequest = [
            'title' => 'test',
            'content' => 'test',
        ];
        $response = post(route('posts.store'), $postRequest);

        expect($response)->redirectToLogin();
    });

    test('unable to view the edit page with post detail', function () {
        $post = fakePost();

        $response = $this->get(route("posts.edit", $post->id));

        expect($response)->redirectToLogin();
    });

    test('unable to update the post with permission', function () {
        $user = fakeUser();
        $post = Post::factory()->createdBy($user->id)->create();

        $postRequest = [
            'title' => 'Updated Title',
            'content' => 'Updated Content'
        ];

        $response = $this->put(route("posts.update", $post->id), $postRequest);
        expect($response)->redirectToLogin();
    });

    test('unable to delete the post with permission', function () {
        $user = fakeUser();
        $post = Post::factory()->createdBy($user->id)->create();

        $response = $this->delete(route("posts.destroy", $post->id));

        expect($response)->redirectToLogin();
    });
});



it('fail to store the post without title and content', function (?string $title, ?string $content) {

    $postRequest = [
        'title' => $title,
        'content' => $content,
    ];
    $response = actAsCustomer()->from(route('posts.create'))->post(route('posts.store'), $postRequest);

    $this->assertDatabaseCount('posts', 0);

    $response->assertStatus(302)
        ->assertSessionHasErrors([(!$title  ? 'title' : null), (!$content ? 'content' : null)])
        ->assertRedirect(route('posts.create'));
})->with([
    [null, 'test content'],
    ['test title', null]
])->group('validation'); // dataset example
