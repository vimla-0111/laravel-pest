<?php

namespace App\Http\Controllers;

use App\Events\ChatDeleted;
use App\Events\ChatRead;
use App\Events\SentPrivateMessage;
use App\Events\UserConversation;
use App\Models\Chat;
use App\Models\Conversation;
use App\Models\ConversationUser;
use App\Models\User;
use App\Services\ChatService;
use App\Traits\Helper;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

use function Laravel\Prompts\info;

class ChatController extends Controller
{
    use Helper;

    public function __construct(protected ChatService $chatService) {}

    public function index(): View
    {
        $users = $this->chatService->getUsersList(Auth::user());

        // dd($users->toArray());
        return view('chat_page', ['users' => $users]);
    }

    public function getFilteredUsersList(Request $request): JsonResponse
    {
        $searchedValue = $request->input('searchTerm', null);
        $users = $this->chatService->getUsersList(Auth::user(), $searchedValue);

        return response()->json(['users' => $users]);
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

        if (! $conversation) {
            $conversation = Conversation::create(['type' => 'private']);
            $conversation->users()->attach([$currrentUser->id, $request->recipient_id]);
            $conversation->load('latestMessage');
        }

        // $receiver = $conversation->users()->where('users.id', '!=', $currrentUser->id)->get(['users.id', 'users.name']);

        return response()->json(['conversation_id' => $conversation->id, 'latest_message' => $conversation?->latestMessage?->media_path ? 'media' : $conversation?->latestMessage?->message ?? null]);
    }

    public function getConversationMessages($conversation_id): JsonResponse
    {
        $conversation = Conversation::find($conversation_id);
        if (! $conversation) {
            return response()->json(['messages' => []]);
        }
        $messages = $conversation->chats()->with('sender')->get();

        return response()->json(['messages' => $messages]);
    }

    public function sendConversationMessages(Request $request, string $conversation_id): JsonResponse
    {
        $request->validate([
            'body' => ['nullable', 'required_without:image', 'string', 'max:255'],
            'image' => ['nullable', 'required_without:body', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
        ]);

        try {
            $path = null;
            if ($request->image) {
                $path = $this->storeMedia($request->image);
            }

            $conversation = Conversation::find($conversation_id);
            if (! $conversation) {
                return response()->json(['error' => 'Conversation not found'], 404);
            }

            DB::beginTransaction();
            $chat = $conversation->chats()->create([
                'sender_id' => $request->user()->id,
                'message' => $request->body,
                'media_path' => $path,
            ]);
            DB::commit();

            $chat->load('sender');

            // Broadcast the message to the conversation channel
            broadcast(new SentPrivateMessage($chat));

            $data = $this->chatService->getUpdatedUserConversation($conversation_id);
            broadcast(new UserConversation($request->selectedUserId, $data));

            return response()->json($chat);
        } catch (\Throwable $th) {
            // throw $th;
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
            'messages.*.read_at' => 'required|date',
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
            }
        }

        return response()->json(['success' => true]);
    }

    public function getUserForNewConversation()
    {
        $conversationIds = ConversationUser::where('user_id', auth()->id())->pluck('conversation_id');
        $userIds = ConversationUser::whereNot('user_id', auth()->id())->whereIn('conversation_id', $conversationIds)->pluck('user_id');

        // dd( $userIds->toRawSql());

        $users = User::isCustomer()
            ->whereNot('id', auth()->id())
            ->whereNotIn('id', $userIds)
            ->get(['id', 'name']);

        return response()->json(['users' => $users]);
        dd($conversationIds, $userIds);
    }

    public function deleteChat(Request $request): JsonResponse
    {
        // $request->dd();
        $request->validate([
            'conversation_id' => ['required', 'exists:conversations,id'],
            'ids.*' => ['required', 'exists:chats,id']
        ]);

        try {
            Log::info('delete chat event start');
            broadcast(new ChatDeleted($request->ids, $request->conversation_id))->toOthers();
        } catch (\Throwable $th) {
            Log::info('error during broadcast chat delete event');
            Log::info($th);
        }

        DB::transaction(function () use ($request) {
            Chat::where('conversation_id', $request->conversation_id)->whereIn('id', $request->ids)->chunkById(10, function ($chats) {
                foreach ($chats as $chat) {
                    if ($chat->media_path) {
                        Storage::delete($chat->media_path);
                    }
                    $chat->delete();
                }
            });
        });

        return response()->json(['message' => 'selected chat deleted']);
    }
}

// $user = User::isCustomer()
//     ->whereNot('users.id', auth()->user()->id)
//     ->join('chats', 'chats.sender_id', '=', 'users.id')
//     ->where('chats.sender_id', '!=', auth()->id())
//     ->whereIn('chats.conversation_id', $conversationIds)
//     ->select('users.*')
//     ->groupBy('chats.conversation_id')
//     ->toRawSql();
