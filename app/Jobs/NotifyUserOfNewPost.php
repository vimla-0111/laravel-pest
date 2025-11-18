<?php

namespace App\Jobs;

use App\Mail\UserNewPostNotification;
use App\Models\Post;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

use function Laravel\Prompts\info;

class NotifyUserOfNewPost implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Post $post,
        public User $user // Using public property promotion
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        info('job dispatched');
        if ($this->user) {
            info('sending mail');
            Mail::to($this->user->email)->send(new UserNewPostNotification($this->post));
        }
    }
}
