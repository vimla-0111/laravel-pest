<?php

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('private_chat', function ($user) {
    Log::info($user->role);
    Log::info($user->role == 'customer');
    return  $user->role == 'customer';
});


Broadcast::channel('chat.{conversation_id}', function ($user, $conversation_id) {
    $conversation = Conversation::find($conversation_id);
    if (!$conversation) {
        return false;
    }
    return  $conversation->hasUser($user);
});

// We use a 'presence' channel (indicated by the join logic in frontend)
Broadcast::channel('global_chat', function ($user) {
    // Return the user info you want visible to others in the "here" method
    // if ($user->role = User::CUSTOMER_ROLE && $user->email_verified_at) {
    if ($user->role = User::CUSTOMER_ROLE) {
        return ['id' => $user->id, 'name' => $user->name];
    }
    return false;
});
