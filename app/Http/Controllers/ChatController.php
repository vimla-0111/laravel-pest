<?php

namespace App\Http\Controllers;

use App\Events\SentPrivateMessage;
use App\Models\Conversation;
use App\Models\ConversationUser;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Traits\Helper;

class ChatController extends Controller
{
    use Helper;
    public function index()
    {
        // dd(Auth::user()->conversations()->get());
        // $users = User::with(['conversations.latestMessage'])
        //     ->where('role', User::CUSTOMER_ROLE)
        //     ->whereNot('id', auth()->user()->id)
        //     ->get(['id','name']);

        // dd(auth()->user()->id,$users->toArray());

        $conversationIds = Auth::user()->conversations()->pluck('conversations.id');
        $users = User::where('role', User::CUSTOMER_ROLE)
            ->whereNot('id', auth()->user()->id)
            ->with([
                'conversations' => function ($q) use ($conversationIds) {
                    return $q->whereIn('conversations.id', $conversationIds)
                        ->with('latestMessage');
                }
            ])
            ->get(['id', 'name'])
            ->map(function ($user) {
                $convo = $user->conversations->first();
                $user->last_message = $convo?->latestMessage?->media_path ? 'media' :  $convo?->latestMessage?->message ?? null;
                return $user;
            });

        return view('chat_page', ['users' => $users]);
    }

    public function createConversation(Request $request)
    {
        $validator = Validator::make($request->all(), ['recipient_id' => 'required']);
        if ($validator->fails()) {
            throw new Exception('invalid selected user');
        }

        $currrentUser = $request->user();
        $conversation = Conversation::whereHas('users', function ($q) use ($currrentUser) {
            $q->where('users.id', $currrentUser->id);
        })->whereHas('users', function ($q) use ($request) {
            $q->where('users.id', $request->recipient_id);
        })->with('latestMessage')
            ->first();

        if (!$conversation) {
            $conversation = Conversation::create(['type' => 'private']);
            $conversation->users()->attach([$currrentUser->id, $request->recipient_id]);
            $conversation->load('latestMessage');
        }

        return response()->json(['conversation_id' => $conversation->id, 'latest_message' => $conversation?->latestMessage?->media_path ? 'media' :  $conversation?->latestMessage?->message ?? null]);
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
        $request->validate([
            'body' => ['nullable', 'required_without:image', 'string', 'max:255'],
            'image' => ['nullable', 'required_without:body', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048']
        ]);

        try {
            $path = null;
            if ($request->image) {
                $path = $this->storeMedia($request->image);
            }

            $conversation = Conversation::find($conversation_id);
            if (!$conversation) {
                return response()->json(['error' => 'Conversation not found'], 404);
            }

            DB::beginTransaction();
            $chat = $conversation->chats()->create([
                'sender_id' => $request->user()->id,
                'message' => $request->body,
                'media_path' => $path
            ]);
            DB::commit();

            $chat->load('sender');

            // Broadcast the message to the conversation channel
            broadcast(new SentPrivateMessage($chat));

            return response()->json($chat);
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            $path ? unlink( $path ) : null;
            dd($th);
            return response()->json(status: 500);
        }
    }
}
