<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use App\Models\User;
use App\Notifications\NewPost;
use App\Services\PostService;
use Illuminate\Http\RedirectResponse;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $posts = Post::latest()->paginate(10)->through(function ($post) {
            $post->append('formatted_published_at');
            return $post;
        });
        // dd($posts);
        return view('posts.index', compact('posts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('posts.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePostRequest $request, PostService $postService): RedirectResponse
    {
        info('storing the post');
        // Create the post and link it to the authenticated user
        $post = $postService->createPost(auth()->user(), $request->validated());
        // Auth::user()->posts()->create($request->validated());

        User::where('role', User::ADMIN_ROLE)->first()->notify(new NewPost($post));
        return redirect()->route('posts.index')
            ->with('success', 'Post created successfully!');
    }

    /**
     * Display the specified resource.
     */
    // public function show(Post $post): View
    // {
    // Not typically used in a simple CRUD, but good to have.
    // return view('posts.show', compact('post'));
    // }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Post $post): View
    {
        return view('posts.edit', compact('post'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePostRequest $request, Post $post): RedirectResponse
    {
        // Check if the user is authorized to update this post
        Gate::authorize('update', $post);

        $post->update($request->validated());

        return redirect()->route('posts.index')
            ->with('success', 'Post updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post): RedirectResponse
    {
        // Check if the user is authorized to delete this post
        Gate::authorize('delete', $post);

        $post->delete();

        return redirect()->route('posts.index')
            ->with('success', 'Post deleted successfully!');
    }
}
