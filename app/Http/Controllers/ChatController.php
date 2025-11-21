<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\ConversationUser;
use App\Models\User;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index()
    {
        return view('chat_page', ['users' => User::where('role', User::CUSTOMER_ROLE)->get(['id', 'name'])]);
    }

    public function createConversation(Request $request)
    {
        // dd($request->recipient_id);
        $currrentUser = $request->user();
        $conversation = Conversation::whereHas('users', function ($q) use ($currrentUser) {
            $q->where('user_id', $currrentUser->id);
        })->whereHas('users', function ($q) use ($request) {
            $q->where('user_id', $request->recipient_id);
        })->first();

        if (!$conversation) {
            $conversation = Conversation::create(['type' => 'private']);
            $conversation->users()->attach([$currrentUser->id, $request->recipient_id]);
        }

        // $conversationId = ConversationUser::whereIn('user_id', [$request->recipient_id, auth()->user()->id])->value('conversation_id');
        // if (!$conversationId) {
        //     $request->user()->conversations()->attach(auth()->user()->id);
        // }
        return response()->json(['conversation_id' => $conversation->id]);
    }
}
