<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request): View
    {
        $posts = $this->getLatestPosts($request);
        return view('dashboard', compact('posts'));
    }

    public function getLatestPosts(): LengthAwarePaginator
    {
        return Post::published()->with('creator')->latest('published_at')->paginate(9)->through(function ($post) {
            $post->append('formatted_published_at');
            return $post;
        });
    }
}
