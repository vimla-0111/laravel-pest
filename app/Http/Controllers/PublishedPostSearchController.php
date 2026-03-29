<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublishedPostSearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'query' => ['nullable', 'string', 'max:255'],
        ]);

        $search = trim((string) ($validated['search'] ?? $validated['query'] ?? ''));

        if ($search === '') {
            $posts = Post::published()
                ->with('creator')
                ->latest('published_at')
                ->paginate(10)
                ->withQueryString()
                ->through(function ($post) {
                    $post->append('formatted_published_at');

                    return $post;
                });

            return view('posts.discover', [
                'posts' => $posts,
                'search' => $search,
            ]);
        }

        $posts = Post::search($search)
            ->query(fn (Builder $query) => $query->with('creator'))
            ->paginate(10)
            ->appends(['search' => $search])
            ->through(function ($post) {
                $post->append('formatted_published_at');

                return $post;
            });

            // dd($posts);
        return view('posts.discover', [
            'posts' => $posts,
            'search' => $search,
        ]);
    }
}
