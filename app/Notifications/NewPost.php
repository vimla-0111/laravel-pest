<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class NewPost extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(protected $post)
    {
        Log::info('NewPost notification created for post ID: ' . ($post?->id ?? 'null'));
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        Log::info('notification printed');
        Log::info($notifiable);
        $data = [
            'created_at' => now(),
            'data' => [
                'post_id' => $this->post?->id,
                'title'   => 'New post: ' . $this->post?->title ?? 'New post',
                'message' => 'A new post was published by ' . $this->post?->creator?->name,
            ]
        ];
        return new BroadcastMessage($data);
    }

    // the array will be stored in data column of the notification table
    public function toDatabase($notifiable)
    {
        Log::info('storing notification');
        return [
            'post_id' => $this->post?->id,
            'title'   => 'New post: ' . $this->post?->title ?? 'New post',
            'message' => 'A new post was published by ' . $this->post?->creator?->name,
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    // public function toArray(object $notifiable): array
    // {
    //     return [];
    // }
}
