<?php

namespace App\Http\Controllers;

use App\Events\ChatDeleted;
use App\Exceptions\ChatException;
use App\Services\ChatService;
use App\Traits\Helper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

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
            // throw new Exception('invalid selected user');
            return response()->json(['error' => 'invalid selected user']);
        }

        $currrentUser = $request->user();
        $conversation = $this->chatService->createConversationIfNotExists($currrentUser, $request->recipient_id);
        // $receiver = $conversation->users()->where('users.id', '!=', $currrentUser->id)->get(['users.id', 'users.name']);

        return response()->json(['conversation_id' => $conversation->id, 'latest_message' => $conversation?->latestMessage?->media_path ? 'media' : $conversation?->latestMessage?->message ?? null]);
    }

    public function getConversationMessages($conversation_id): JsonResponse
    {
        try {
            $messages = $this->chatService->getConversationMessages($conversation_id);
            return response()->json(['messages' => $messages]);
        } catch (ChatException $e) {
            return response()->json(['messages' => [], 'error' => $e->getMessage()]);
        } catch (\Throwable $th) {
            return response()->json(['messages' => [], 'error' => 'Something went wrong!']);
        }
    }

    public function sendConversationMessages(Request $request, string $conversation_id): JsonResponse
    {
        $request->validate([
            'body' => ['nullable', 'required_without:image', 'string', 'max:255'],
            'image' => ['nullable', 'required_without:body', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
        ]);

        try {
            $chat = $this->chatService->sendMessage($request, $conversation_id);
            return response()->json($chat);
        } catch (ChatException $e) {
            return response()->json(['messages' => [], 'error' => $e->getMessage()]);
        } catch (\Throwable $th) {
            // dd($th);
            return response()->json(['error' => 'something went wrong'], status: 500);
        }
    }

    public function markChatAsRead(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'messages.*.messageId' => 'required|integer|exists:chats,id',
                'messages.*.read_at' => 'required|date',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }
            $this->chatService->markChatAsRead($request);
            return response()->json(['success' => true]);
        } catch (ChatException $e) {
            return response()->json(['messages' => [], 'error' => $e->getMessage()]);
        } catch (\Throwable $th) {
            return response()->json(['error' => 'something went wrong'], status: 500);
        }
    }

    public function getUserForNewConversation()
    {
        try {
            $users = $this->chatService->getUserForNewConversation(auth()->id());
            return response()->json(['users' => $users]);
        } catch (\Throwable $th) {
            // dd($th);
            return response()->json(['error' => 'something went wrong'], status: 500);
        }
    }

    public function deleteMultipleChats(Request $request)
    {
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

        try {
            DB::transaction(function () use ($request) {
                $this->chatService->deleteChatsWithMedia(
                    $request->conversation_id,
                    $request->ids
                );
            });
            return response()->json(['message' => 'Chats deleted successfully']);
        } catch (\Throwable $th) {
            return response()->json(['error' => 'something went wrong'], status: 500);
        }
    }
}