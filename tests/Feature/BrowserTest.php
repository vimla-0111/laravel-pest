<?php

use App\Models\Post;
use App\Models\User;

test('example', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

test('visit the home page', function () {
    $page = visit('/');

    $page->assertSee('Log in');   // check that Log in text is visible in the page
});


// test('visit the home page by auth user', function () {
//     $page = visit('/');

//     $page->actingAs(fakeUser())->assertSee('Dashboard');   // check that Log in text is visible in the page
// });


it('may log in the user', function () {

    User::factory()->create([ // assumes RefreshDatabase trait is used on Pest.php...
        'email' => 'test@gmail.com',
        'password' => 'password',
    ]);

    $page = visit('/')->on()->mobile();

    $page->click('Log in')
        ->assertUrlIs(route('login'))
        ->assertSee('Forgot your password?')
        ->fill('email', 'test@gmail.com')
        ->fill('password', 'password')
        ->click('Log in')
        ->assertSee('Dashboard')
        ->assertSee('You\'re logged in!');

    $this->assertAuthenticated();
});

it('navigate to post', function () {
    $this->actingAs(fakeUser());
    $page = visit(route('dashboard'));

    $page->navigate(route('posts.index'))
        ->assertSee('Create Post');
    // The assertNoSmoke method asserts there are no console logs or JavaScript errors on the page
    $page->assertNoSmoke();
});


it('create post', function () {
    $this->actingAs(fakeUser());

    $page = visit(route('posts.create'));
    $page->assertSee('Create Post')
        ->assertPresent('#create-form')
        ->typeSlowly('title', 'writing title')
        ->type('content', 'writing content')
        ->submit()          // submit the first form
        ->assertUrlIs(route('posts.index'))
        ->assertSee('Posts')
        ->assertSee('writing title');
});



it('update post', function () {
    $user = fakeUser();
    $this->actingAs($user);
    $post = Post::factory()->createdBy($user->id)->create();

    $page = visit(route('posts.edit', $post->id));
    $page->assertSee('Edit Post')
        ->typeSlowly('title', 'updating title')
        ->type('content', 'updating content')
        ->submit()          // submit the first form
        ->assertUrlIs(route('posts.index'))
        ->assertSee('Posts')
        ->assertSee('updating title');
});


it('delete post', function () {
    $user = fakeUser();
    $this->actingAs($user);
    Post::create([
        'title' => 'test title',
        'content' => 'test content',
        'created_by' => $user->id
    ]);

    $page = visit(route('posts.index'));
    $page->assertSee('Posts')
        ->click('.delete-btn:nth-of-type(1)')
        ->assertSee('Delete Post')
        ->assertSee('Are you sure you want to delete this post? This action cannot be undone.')
        ->click('#delete-confirm-btn')
        ->assertUrlIs(route('posts.index'))
        ->assertDontSee('test title');
});


it('guest is redirected to login when accessing protected pages', function () {
    // list protected routes to check
    $protected = [
        route('dashboard'),
        route('posts.create'),
    ];

    foreach ($protected as $uri) {
        $page = visit($uri);
        $page->assertUrlIs(route('login'))
            ->assertSee('Log in');
    }

    // also check edit route for an existing post
    $post = Post::factory()->create();
    $page = visit(route('posts.edit', $post->id));
    $page->assertUrlIs(route('login'))
        ->assertSee('Log in');
});
