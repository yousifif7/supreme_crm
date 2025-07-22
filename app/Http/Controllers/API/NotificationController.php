<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\DeviceToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'type' => 'nullable|in:alarm,message,alert,approval',
            'read' => 'nullable|string',
            'page' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Notification::where('user_id', Auth::id());

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('read')) {
            // Accept "true", "false", 1, 0 as strings and convert properly
            $read = filter_var($request->read, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if (!is_null($read)) {
                $query->where('read', $read);
            }
        }

        $notifications = $query->latest()->paginate($request->limit ?? 20);

        return response()->json([
            'notifications' => $notifications->items(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    public function markAsRead($id)
    {
        $notification = Notification::where('user_id', Auth::id())
            ->findOrFail($id);

        $notification->update(['read' => true]);

        return response()->json(['message' => 'Notification marked as read']);
    }

    public function registerDevice(Request $request)
    {
        $request->validate([
            'push_token' => 'required|string',
            'platform' => 'required|in:ios,android'
        ]);

        DeviceToken::updateOrCreate(
            ['user_id' => Auth::id(), 'push_token' => $request->push_token],
            ['platform' => $request->platform]
        );

        return response()->json(['message' => 'Device registered']);
    }
}
