<?php

use App\Http\Controllers\MessageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// // Public routes (anyone can see)
// Route::get('/posts', [PostController::class, 'index'])->name('post.index');
// Route::get('/posts/{post}', [PostController::class, 'show'])->name('post.show');

// // Protected routes (must be logged in)
// Route::middleware('auth:web')->group(function () {
//     Route::post('/posts', [PostController::class, 'store'])->name('post.store');
//     Route::put('/posts/{post}', [PostController::class, 'update'])->name('post.update');
//     Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('post.destroy');
// });

Route::resource('posts', PostController::class)
     ->middleware('auth:web');

Route::middleware('auth')->group(function () {
    Route::get('/chat', [MessageController::class, 'index'])->name('chat');
    Route::post('/chat', [MessageController::class, 'store']);
});

require __DIR__ . '/auth.php';
