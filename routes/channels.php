<?php

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

// customer