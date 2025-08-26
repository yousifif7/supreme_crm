<?php

namespace App\Http\Controllers\API;

use App\Models\Employee;
use App\Models\DeviceToken;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\DatabaseNotification;

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

        // Get the currently authenticated user
        $user = Auth::user();

        // Find the linked employee
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            return response()->json(['message' => 'Employee not found for this user.'], 404);
        }

        // Start notifications query, filtered by employee_id
        $query = Notification::where('employee_id', auth::id())
        ->orWhere('employee_id',Auth::id());

        // Optional filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('read')) {
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
        $notification = Notification::findOrFail($id);

        $notification->update(['read' => true]);

        return response()->json(['message' => 'Notification marked as read']);
    }

    public function registerDevice(Request $request)
    {
        $request->validate([
            'push_token' => 'required|string',
            'platform' => 'required|in:ios,android'
        ]);

        // $employee = Employee::find($request->user_id);

        // if (!$employee) {
        //     return response()->json(['message' => 'Employee not found'], 404);
        // }

        $employee = Employee::where('user_id',Auth::id())->first();
        
        DeviceToken::where('push_token', $request->push_token)->delete();
        
        DeviceToken::updateOrCreate(
            ['user_id' => Auth::id()],
            [

                'push_token' => $request->push_token,
                'platform'   => $request->platform
            ]
        );


        return response()->json(['message' => 'Device registered']);
    }

    public function markAllRead()
    {
        // \Log::info('Mark all read hit');
        Notification::where('read', false)
            ->update(['read' => true]);

        return redirect()->back()->with('success', 'All notifications marked as read.');
    }

    public function markSelectedRead(Request $request)
    {
        $ids = $request->input('ids');

        if (!$ids || !is_array($ids)) {
            return redirect()->back()->with('error', 'No notifications selected.');
        }

        Notification::whereIn('id', $ids)->update(['read' => true]);

        return redirect()->back()->with('success', 'Selected notifications marked as read.');
    }
}
