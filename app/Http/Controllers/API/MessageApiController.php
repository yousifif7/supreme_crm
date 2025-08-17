<?php

// app/Http/Controllers/API/MessageApiController.php
namespace App\Http\Controllers\API;

use App\Models\Message;
use App\Models\MessageRead;
use App\Models\User;
use App\Models\Conversation;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
// assume models: Conversation, Message, ConversationUser

class MessageApiController extends Controller
{
    public function getConversations()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

    $convos = $user->conversations()->with(['participants', 'latestMessage.sender'])->get();

    $list = $convos->map(fn($c) => [
        'id' => $c->id,
        'type' => $c->type,
        'name' => $c->name,
        'participants' => $c->participants->map(fn($p) => [
            'user_id' => $p->id,  
            'name' => $p->name,
            'role' => $p->getRoleNames()->first(),
        ]),
        'last_message' => $c->latestMessage ? [
            'message' => $c->latestMessage->message,
            'timestamp' => $c->latestMessage->created_at->toDateTimeString(),
            'sender_name' => $c->latestMessage->sender?->name ?? 'Unknown',
        ] : null,
        'unread_count' => $c->participants
            ->firstWhere('id', $user->id)?->pivot->unread_count ?? 0,  
    ]);
        return response()->json(['conversations' => $list]);
    }


    public function getMessages(Request $req, $conversation)
    {
        $limit = $req->query('limit', 50);
        $messages = Message::where('conversation_id', $conversation)
            ->with('sender')
            ->orderBy('created_at', 'desc')
            ->paginate($limit);

        $msgs = $messages->map(fn($m) => [
            'id' => $m->id,
            'sender_id' => $m->sender_id,
            'sender_name' => trim(($m->sender->name ?? ''). ' '. ($m->sender->first_name ?? '') . ' ' . ($m->sender->last_name ?? '')),
            'message' => $m->message,
            'message_type' => $m->type,
            'media_url' => $m->media_url,
            'timestamp' => $m->created_at->toDateTimeString(),
            'read_by' => $m->readByUserIds(),
            'deleted' => $m->deleted,
        ]);

        return response()->json(['messages' => $msgs]);
    }

    public function sendMessage(Request $req)
    {
        $req->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'message' => 'required_without:media_file|string',
            'media_file' => 'nullable|string',
        ]);

        $msg = Message::create([
            'conversation_id' => $req->conversation_id,
            'sender_id' => Auth::id(),
            'message' => $req->message,
            'media_file' => $req->media_file ? $this->storeBase64Media($req->media_file) : null,
        ]);

        // Optionally broadcast via real-time etc.

        return response()->json(['message_id' => $msg->id]);
    }

    public function markRead(Request $request)
    {
        $request->validate([
            'message_ids' => 'required|array',
            'message_ids.*' => 'integer|exists:messages,id',
        ]);

        $userId = Auth::id();

        foreach ($request->message_ids as $messageId) {
            MessageRead::updateOrCreate(
                ['message_id' => $messageId, 'user_id' => $userId],
                ['read_at' => now()]
            );
        }
        return response()->json(['message' => 'marked as read']);
    }

    protected function storeBase64Media($b64)
    {
        $data = base64_decode($b64);
        // store logic here, return URL
        return Storage::disk('public')->put('messages', $data);
    }
    
}
