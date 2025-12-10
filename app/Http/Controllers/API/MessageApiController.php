<?php

// app/Http/Controllers/API/MessageApiController.php
namespace App\Http\Controllers\API;

use App\Models\User;
use App\Models\Message;
use App\Events\MessageSent;
use App\Models\MessageRead;
use App\Models\Conversation;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
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

        $convos = $user->conversations()->with(['participants', 'latestMessage.sender'])->latest('updated_at')->get();

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
                'attachment' => $c->latestMessage->attachment, // 👈 add this
                'timestamp' => $c->latestMessage->created_at->toDateTimeString(),
                'sender_name' => $c->latestMessage->sender?->first_name ?? 'Unknown',
            ] : null,
            'unread_count' => $c->participants
                ->firstWhere('id', $user->id)?->unread_count ?? 0,
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
            'sender_name' => trim(($m->sender->name ?? '') . ' ' . ($m->sender->first_name ?? '') . ' ' . ($m->sender->last_name ?? '')),
            'message' => $m->message,
            'message_type' => $m->type,
            'attachment' => $m->attachment,
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
            'message' => 'required_without:attachment|string',
            'attachment' => 'nullable|file|max:10240', // base64 file
        ]);

        $attachmentPath = null;

        if ($req->attachment) {
            $attachmentPath = $this->storeBase64Media($req->attachment);
        }

        $msg = Message::create([
            'conversation_id' => $req->conversation_id,
            'sender_id' => Auth::id(),
            'message' => $req->message ?? 'Attachment',
            'attachment' => $attachmentPath, // 👈 match dashboard-side column
        ]);

        // Broadcast (optional, if using websockets)
        broadcast(new MessageSent($msg))->toOthers();

        return response()->json([
            'message_id' => $msg->id,
            'attachment' => $msg->attachment,
        ]);
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

    protected function storeBase64Media($base64String)
    {
        $data = explode(',', $base64String);
        $fileData = base64_decode(end($data));

        $finfo = finfo_open();
        $mimeType = finfo_buffer($finfo, $fileData, FILEINFO_MIME_TYPE);

        $extension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'application/pdf' => 'pdf',
            default => 'bin',
        };

        $filename = uniqid() . '.' . $extension;
        $directory = public_path('message_attachments');

        // ✅ Create directory if it doesn't exist
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true); // recursive mkdir
        }

        $path = $directory . '/' . $filename;

        file_put_contents($path, $fileData);

        return url('message_attachments/' . $filename); // return full URL
    }

    public function createConversation(Request $request)
    {
        $request->validate([
            'participants' => 'required|array|min:1',
            'participants.*' => 'exists:users,id',
            'name' => 'nullable|string', // only for groups
        ]);

        $authUser = Auth::user();
        $participantIds = array_unique($request->participants);

        // Always include the creator
        $allParticipantIds = array_unique(array_merge($participantIds, [$authUser->id]));

        if (count($participantIds) === 1) {
            // 🔹 DIRECT conversation
            $otherUserId = $participantIds[0];

            // Check if one already exists
            $existing = Conversation::where('type', 'direct')
                ->whereHas('participants', fn($q) => $q->where('users.id', $authUser->id))
                ->whereHas('participants', fn($q) => $q->where('users.id', $otherUserId))
                ->first();

            if ($existing) {
                return response()->json([
                    'conversation_id' => $existing->id,
                    'type' => $existing->type,
                    'name' => $existing->name,
                    'existing' => true, // frontend can open instead of creating duplicate
                    'participants' => $existing->participants()
                        ->select('users.id', 'users.first_name', 'users.last_name')
                        ->get(),
                ]);
            }

            // Otherwise create a new one
            $conversation = Conversation::create([
                'type' => 'direct',
                'name' => null,
            ]);
            $conversation->participants()->attach($allParticipantIds);
        } else {
            // 🔹 GROUP conversation
            $conversation = Conversation::create([
                'type' => 'group',
                'name' => $request->name ?? 'Group Chat',
            ]);
            $conversation->participants()->attach($allParticipantIds);
        }

        // Get participants with unambiguous column selection
        $participants = $conversation->participants()
            ->select('users.id', 'users.first_name', 'users.last_name')
            ->get();

        return response()->json([
            'conversation_id' => $conversation->id,
            'type' => $conversation->type,
            'name' => $conversation->name,
            'participants' => $participants,
        ], 201);
    }

    public function searchUsers(Request $request)
    {
        $authUser = Auth::user();
        $query = $request->input('name', null);
        $roleFilter = $request->input('role', null); // optional role filter

        // 1) Get all users the guard already has direct conversations with
        $existingDirectUserIds = Conversation::where('type', 'direct')
            ->whereHas('participants', fn($q) => $q->where('users.id', $authUser->id))
            ->with('participants')
            ->get()
            ->flatMap(fn($c) => $c->participants->pluck('id'))
            ->reject(fn($id) => $id == $authUser->id)
            ->unique()
            ->values();

        // 2) Query users
        $users = User::query()
            ->when($query, function ($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                    ->orWhere('last_name', 'like', "%{$query}%")
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$query}%"]);
            })
            ->when($roleFilter, function ($q) use ($roleFilter) {
                // fuzzy role search
                $q->whereHas('roles', function ($q) use ($roleFilter) {
                    $q->where('name', 'like', "%{$roleFilter}%");
                });
            })
            ->where('id', '!=', $authUser->id)
            ->whereNotIn('id', $existingDirectUserIds)
            ->with('roles')
            ->select('id', 'first_name', 'last_name', 'email')
            ->orderBy('first_name')
            ->paginate(20);

        // 3) Append role to each user
        $users->getCollection()->transform(function ($user) {
            return [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'role' => $user->getRoleNames()->first(),
            ];
        });

        // 4) Friendly message if no users found
        if ($users->isEmpty()) {
            return response()->json([
                'message' => 'No users found matching your search criteria.',
                'users' => [],
            ]);
        }

        return response()->json($users);
    }

    public function roles()
    {
        $roles = Role::all();

        $result = [];

        foreach ($roles as $role) {
            $result[] = [
                'id'   => $role->id,
                'role' => $role->name,
            ];
        }

        return $result;
    }
}
