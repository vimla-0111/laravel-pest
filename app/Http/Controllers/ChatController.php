<?php

namespace App\Http\Controllers;

use App\Events\ChatRead;
use App\Events\SentPrivateMessage;
use App\Models\Chat;
use App\Models\Conversation;
use App\Models\ConversationUser;
use App\Models\User;
use App\Services\ChatService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Traits\Helper;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ChatController extends Controller
{
    use Helper;

    public function __construct(protected ChatService $chatService) {}

    public function index(): View
    {
        // dd(Auth::user()->conversations()->get());
        // $users = User::with(['conversations.latestMessage'])
        //     ->where('role', User::CUSTOMER_ROLE)
        //     ->whereNot('id', auth()->user()->id)
        //     ->get(['id','name']);

        // dd(auth()->user()->id,$users->toArray());

        $conversationIds = Auth::user()->conversations()->pluck('conversations.id');
        $users = User::isCustomer()
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

    public function createConversation(Request $request): JsonResponse
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

    public function getConversationMessages($conversation_id): JsonResponse
    {
        $conversation = Conversation::find($conversation_id);
        if (!$conversation) {
            return response()->json(['messages' => []]);
        }
        $messages = $conversation->chats()->with('sender')->get();
        return response()->json(['messages' => $messages]);
    }

    public function sendConversationMessages(Request $request, string $conversation_id): JsonResponse
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
            $path ? unlink($path) : null;
            dd($th);
            return response()->json(status: 500);
        }
    }

    public function markChatAsRead(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'messages.*.messageId' => 'required|integer|exists:chats,id',
            'messages.*.read_at' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        foreach ($request['messages'] as $message) {
            $chat = Chat::find($message['messageId']);
            Log::info('Marking chat as read: ' . $chat->id . ' at ' . $message['read_at']);
            if ($chat) {
                $chat->read_at = Carbon::parse($message['read_at']);
                $chat->save();
                broadcast(new ChatRead($chat))->toOthers();
                // $chat->update(['read_at' => Carbon::parse($message['read_at'])]);
                // broadcast(new ChatRead($chat->refresh()));
            }
        }

        return response()->json(['success' => true]);
    }
}
