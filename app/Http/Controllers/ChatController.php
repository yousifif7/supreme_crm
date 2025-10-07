<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\MessageRead;
use App\Models\UserPinnedConversation;
use Auth;
use App\Events\MessageSent;
use App\Events\UserTyping;
use App\Notifications\DbNotification;

class ChatController extends Controller
{
    public function togglePin(Request $request, $id)
    {
        $user = Auth::user();

        $pinnedConversation = UserPinnedConversation::where('user_id', $user->id)
            ->where('conversation_id', $id)
            ->first();

        if ($request->pinned) {
            if (!$pinnedConversation) {
                UserPinnedConversation::create([
                    'user_id' => $user->id,
                    'conversation_id' => $id,
                ]);
            }
        } else {
            $pinnedConversation?->delete();
        }

        return response()->json(['message' => 'Pin status updated successfully'], 200);
    }

    public function createOneToOneConversation(Request $request)
    {
        try {
            $request->validate([
                'user_id_1' => 'required|exists:users,id',
                'user_id_2' => 'required|exists:users,id',
            ]);

            if ($request->user_id_1 !== auth()->id() && $request->user_id_2 !== auth()->id()) {
                return response()->json(['error' => 'You can only start a chat with yourself or another user.'], 403);
            }

            $conversation = Conversation::where('type', 'direct')
                ->whereHas('participants', function ($query) use ($request) {
                    $query->whereIn('users.id', [$request->user_id_1, $request->user_id_2]);
                }, '=', 2)
                ->first();

            if ($conversation) {
                return response()->json($conversation, 200);
            }

            $conversation = Conversation::create(['type' => 'direct']);
            $conversation->participants()->attach([$request->user_id_1, $request->user_id_2]);

            $user = User::find(Auth::id());
            $user->notify(new DbNotification('New chat added'));

            return response()->json($conversation, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function index()
    {
        $users = User::where('id', '!=', Auth::id())->get();
        return view('chat.index', compact('users'));
    }

    public function viewMembers($conversationId)
    {
        $conversation = Conversation::with('participants')->findOrFail($conversationId);
        return response()->json($conversation->participants);
    }

    public function getConversationMedia($conversationId)
    {
        $messages = Message::where('conversation_id', $conversationId)
            ->whereNotNull('attachment')
            ->where(function ($query) {
                $query->where('attachment', 'like', '%.jpg')
                    ->orWhere('attachment', 'like', '%.jpeg')
                    ->orWhere('attachment', 'like', '%.png')
                    ->orWhere('attachment', 'like', '%.gif')
                    ->orWhere('attachment', 'like', '%.webp')
                    ->orWhere('attachment', 'like', '%.mp4')
                    ->orWhere('attachment', 'like', '%.webm')
                    ->orWhere('attachment', 'like', '%.ogg');
            })
            ->get(['attachment']);

        return response()->json($messages);
    }

    public function markMessagesAsRead($conversationId)
    {
        $user = Auth::user();
        $unreadMessages = Message::where('conversation_id', $conversationId)
            ->whereDoesntHave('readReceipts', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->get();

        foreach ($unreadMessages as $message) {
            MessageRead::create([
                'user_id' => $user->id,
                'message_id' => $message->id,
                'read_at' => now(),
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function createConversation(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'users_id' => 'required|array|min:1',
            'users_id.*' => 'exists:users,id',
            'icon' => 'nullable|image|max:2048',
        ]);

        $currentUser = Auth::user();
        $userIds = $request->users_id;
        $isGroup = count($userIds) > 1 || $request->filled('name');

        if ($request->hasFile('icon')) {
            $icon = $request->file('icon');
            $filename = uniqid() . '.' . $icon->getClientOriginalExtension();
            $icon->move(public_path('conversation_icons'), $filename);
            $iconPath = 'conversation_icons/' . $filename;
        } else {
            $iconPath = null;
        }

        if ($isGroup) {
            $conversation = Conversation::create([
                'type' => 'group',
                'name' => $request->name,
                'icon_path' => $iconPath,
            ]);

            $conversation->participants()->attach(array_merge($userIds, [$currentUser->id]));
        } else {
            $otherUserId = $userIds[0];
            $existing = Conversation::where('type', 'direct')
                ->whereHas('participants', fn($q) => $q->where('user_id', $currentUser->id))
                ->whereHas('participants', fn($q) => $q->where('user_id', $otherUserId))
                ->first();

            if ($existing) {
                return redirect()->back()->with('message', 'Conversation already exists.');
            }

            $conversation = Conversation::create(['type' => 'direct']);
            $conversation->participants()->attach([$currentUser->id, $otherUserId]);
        }

        return redirect()->back()->with('success', 'Conversation created.');
    }

    public function getConversations(Request $request)
    {
        $user = $request->user();
        $search = $request->input('search', '');

        $conversations = Conversation::with([
            'participants',
            'messages' => function ($query) {
                $query->latest()->limit(1);
            }
        ])
            ->whereHas('participants', function ($query) use ($user) {
                $query->where('conversation_user.user_id', $user->id);
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    // Fully qualify the conversations.name search
                    $q->where('conversations.name', 'like', "%$search%")
                        ->orWhereHas('participants', function ($q) use ($search) {
                            // Fully qualify to avoid ambiguity
                            $q->where('users.name', 'like', "%$search%")
                                ->where('users.id', '!=', Auth::id());
                        });
                });
            })
            ->get()
            ->map(function ($conversation) use ($user) {
                // Check if pinned for this user
                $conversation->pinned = $conversation->pinnedByUsers()
                    ->where('user_id', $user->id)
                    ->exists();

                // Add unread count
                $conversation->unread_count = Message::where('conversation_id', $conversation->id)
                    ->whereDoesntHave('readReceipts', function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    })
                    ->count();

                // Add last message preview
                $conversation->last_message = optional($conversation->messages->first())->message;

                return $conversation;
            });

        return response()->json($conversations);
    }


    public function getMessages($conversationId)
    {
        $conversation = Conversation::with(['messages.sender', 'messages.readReceipts', 'participants'])->findOrFail($conversationId);
        return response()->json($conversation);
    }

    public function sendMessage(Request $request, $conversationId)
    {
        if ($request->hasFile('attachment')) {
            $request->validate([
                'message' => 'nullable|string',
                'user_id' => 'required|exists:users,id',
                'attachment' => 'nullable|file|max:10240', // 10MB max
            ]);
        }
        if (!$request->hasFile('attachment')) {
            $request->validate([
                'message' => 'required|string',
                'user_id' => 'required|exists:users,id',
                'attachment' => 'nullable|file|max:10240', // 10MB max
            ]);
        }

        $conversation = Conversation::findOrFail($conversationId);
        $attachmentPath = null;

        if ($request->hasFile('attachment')) {
            $attachment = $request->file('attachment');
            $filename = uniqid() . '.' . $attachment->getClientOriginalExtension();
            $attachment->move(public_path('message_attachments'), $filename);
            $attachmentPath = '/message_attachments/' . $filename;
        }
        $message = $conversation->messages()->create([
            'sender_id' => $request->user_id,
            'message' => $request->message?? 'Attachment',
            'attachment' => $attachmentPath,
        ]);

        // 1) Broadcast (real-time updates via Echo/WebSockets)
        broadcast(new MessageSent($message))->toOthers();

        // 2) Push Notification (mobile notification)
        $sender = User::find($request->user_id);

        $recipients = $conversation->participants()
            ->where('user_id', '!=', $request->user_id)
            ->get();

        foreach ($recipients as $recipient) {
            send_push_notification(
                $recipient->id,
                'New Message',
                $request->message
                    ? "From {$sender?->first_name} {$sender?->last_name}: {$request->message}"
                    : ($attachmentPath
                        ? "📎 Attachment from {$sender?->first_name} {$sender?->last_name}"
                        : "You received a new message"),
                [
                    'conversation_id' => $conversation->id,
                    'message_id' => $message->id,
                ]
            );
        }

        return response()->json($message, 201);
    }

    public function deleteMessage($messageId)
    {
        $message = Message::findOrFail($messageId);

        if ($message->sender_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($message->attachment && file_exists(public_path($message->attachment))) {
            unlink(public_path($message->attachment));
        }

        $message->delete();

        return response()->json(['success' => true]);
    }

    public function userTyping(Request $request, $conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);
        $user = Auth::user();

        broadcast(new UserTyping($conversationId, $user->id, $user->name))->toOthers();

        return response()->json(['success' => true]);
    }

    private function uploadAttachment(Request $request)
    {
        $attachment = $request->file('attachment');
        $filename = uniqid() . '.' . $attachment->getClientOriginalExtension();
        $attachment->move(public_path('message_attachments'), $filename);
        return 'message_attachments/' . $filename;
    }
}
