<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Auth;
use App\Events\MessageSent;
use App\Models\UserPinnedConversation;
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
            // Create a new pin
            UserPinnedConversation::create([
                'user_id' => $user->id,
                'conversation_id' => $id,
            ]);
        }
    } else {
        // Remove the pin if it exists
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

        // Ensure authenticated user is part of the conversation
        if ($request->user_id_1 !== auth()->id() && $request->user_id_2 !== auth()->id()) {
            return response()->json(['error' => 'You can only start a chat with yourself or another user.'], 403);
        }

        // Check if a conversation already exists
        $conversation = Conversation::where('type', 'direct')
            ->whereHas('participants', function ($query) use ($request) {
                $query->whereIn('users.id', [$request->user_id_1, $request->user_id_2]);
            }, '=', 2)
            ->first();

        if ($conversation) {
            return response()->json($conversation, 200);
        }

        // Create a new conversation
        $conversation = Conversation::create(['type' => 'direct']);
        $conversation->participants()->attach([$request->user_id_1, $request->user_id_2]);

        // Send notification
        $user = User::find(Auth::id());
        $user->notify(new DbNotification('New chat added'));

        return response()->json($conversation, 201);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}


    public function index(){

        $users=User::get();
        return view('chat.index',compact('users'));
    }

        public function viewMembers($conversationId)
{
    // Fetch the conversation and include the users related to it
    $conversation = Conversation::with('participants')->findOrFail($conversationId);

    // Return the users of the conversation
    return response()->json($conversation->participants);
}


    public function createConversation(Request $request)
{
    $request->validate([
        'name' => 'nullable|string|max:255',  // optional for one-to-one
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

        // Attach selected users + current user
        $conversation->participants()->attach(array_merge($userIds, [$currentUser->id]));
    } else {
        // One-to-one: Check if conversation already exists
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
        $user = $request->user();  // Get the authenticated user

        // Fetch conversations the user is part of
        $conversations = Conversation::with(['participants']) // Adjust relationships as needed
        ->get()
        ->map(function ($conversation) use ($user) {
            // Check if the conversation is pinned for the user
            $conversation->pinned = $conversation->pinnedByUsers()
                ->where('user_id', $user->id)
                ->exists();

            return $conversation;
        });

        return response()->json($conversations);
    }


        public function getMessages($conversationId)
    {
        $conversation = Conversation::with('messages.sender')->findOrFail($conversationId);

        return response()->json($conversation->messages);
    }

    // Send a message in a conversation (group or one-to-one)

public function sendMessage(Request $request, $conversationId)
{
    $request->validate([
        'message' => 'nullable|string',
        'user_id' => 'required|exists:users,id',
        'attachment' => 'nullable|file|max:2048', // Validate file size and type
    ]);

    $conversation = Conversation::findOrFail($conversationId);

    $attachmentPath = null;

    // Handle file upload
    if ($request->hasFile('attachment')) {
        $attachmentPath = $this->uploadAttachment($request);
    }

    // Store message in the database
    $message = $conversation->messages()->create([
        'sender_id' => $request->user_id,
        'message' => $request->message,
        'attachment' => $attachmentPath, // Save the file path
    ]);

    // Broadcast the message to the conversation's channel
    broadcast(new MessageSent($message));

    return response()->json($message, 201);
}



}
