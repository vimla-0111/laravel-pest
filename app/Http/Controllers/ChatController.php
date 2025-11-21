<?php

namespace App\Http\Controllers;

use App\Events\SentPrivateMessage;
use App\Models\Conversation;
use App\Models\ConversationUser;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    public function index()
    {
        // User::with(['conversations' => function ($q) {
        //     $q->where('user_id', auth()->user()->id);
        // }])->where('role', User::CUSTOMER_ROLE)->whereNot('id', auth()->user()->id)->get(['id', 'name']);
        return view('chat_page', ['users' => User::where('role', User::CUSTOMER_ROLE)->whereNot('id', auth()->user()->id)->get(['id', 'name'])]);
    }

    public function createConversation(Request $request)
    {
        $validator = Validator::make($request->all(),['recipient_id' => 'required']);
        if ($validator->fails()) {
            throw new Exception('invalid selected user');
        }

        $currrentUser = $request->user();
        $conversation = Conversation::whereHas('users', function ($q) use ($currrentUser) {
            $q->where('users.id', $currrentUser->id);
        })->whereHas('users', function ($q) use ($request) {
            $q->where('users.id', $request->recipient_id);
        })->first();

        if (!$conversation) {
            $conversation = Conversation::create(['type' => 'private']);
            $conversation->users()->attach([$currrentUser->id, $request->recipient_id]);
        }

        return response()->json(['conversation_id' => $conversation->id]);
    }

    public function getConversationMessages($conversation_id)
    {
        $conversation = Conversation::find($conversation_id);
        if (!$conversation) {
            return response()->json(['messages' => []]);
        }
        $messages = $conversation->chats()->with('sender')->get();
        return response()->json(['messages' => $messages]);
    }

    public function sendConversationMessages(Request $request, $conversation_id)
    {
        $conversation = Conversation::find($conversation_id);
        if (!$conversation) {
            return response()->json(['error' => 'Conversation not found'], 404);
        }

        $chat = $conversation->chats()->create([
            'sender_id' => $request->user()->id,
            'message' => $request->body,
        ]);

        $chat->load('sender');

        // Broadcast the message to the conversation channel
        broadcast(new SentPrivateMessage($chat));

        return response()->json($chat);
    }
}
