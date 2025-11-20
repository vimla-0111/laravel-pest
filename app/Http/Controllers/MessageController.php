<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use function Laravel\Prompts\info;

class MessageController extends Controller
{
    public function index()
    {
        // Load initial messages
        return view('chat', [
            'messages' => Message::with('user')->latest()->take(50)->get()->reverse()->values()
        ]);
    }

    public function store(Request $request)
    {
        Log::info('starting store in MessageController');
        $request->validate(['text' => 'required']);

        $message = $request->user()->messages()->create([
            'text' => $request->input('text')
        ]);
        Log::info('Message Created in Controller');
        // Broadcast the event to Reverb
        broadcast(new MessageSent($message))->toOthers();

        return response()->json(['status' => 'Message Sent!']);
    }
}
