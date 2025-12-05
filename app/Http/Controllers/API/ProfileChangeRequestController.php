<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProfileChangeRequest;
use App\Models\User;
use App\Models\Notification;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;

class ProfileChangeRequestController extends Controller
{
    // List pending requests (admin)
    public function index(Request $request)
    {

        $requests = ProfileChangeRequest::with('user')->where('status', 'pending')->orderBy('created_at', 'desc')->get();
        return response()->json(['requests' => $requests]);
    }

    // Show single request
    public function show($id)
    {
        $req = ProfileChangeRequest::with('user')->findOrFail($id);
        return response()->json(['request' => $req]);
    }

    // Approve request
    public function approve(Request $request, $id)
    {

        $req = ProfileChangeRequest::findOrFail($id);
        if ($req->status !== 'pending') {
            return response()->json(['message' => 'Request already processed'], 422);
        }

        $user = User::find($req->user_id);
        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // ensure no other user has the requested email
        if (User::where('email', $req->requested_email)->where('id', '<>', $user->id)->exists()) {
            $req->status = 'denied';
            $req->admin_id = Auth::id();
            $req->admin_note = 'Requested email already in use';
            $req->save();
            return response()->json(['message' => 'Requested email already in use'], 422);
        }

        $old = $user->email;
        $user->email = $req->requested_email;
        $user->save();

        // Also update related Employee record if present
        $employee = Employee::where('user_id', $user->id)->first();
        if ($employee) {
            // If Employee has an email field, update it to match the user's email
            if (array_key_exists('email', $employee->getAttributes())) {
                $employee->email = $user->email;
            }
            // Save the employee record
            $employee->save();
        }

        $req->status = 'approved';
        // admin_id may be null if admin UI is unauthenticated in this deployment
        $req->admin_id = Auth::id() ?: null;
        $req->save();

        // Notify user
        Notification::create([
            'user_id' => $user->id,
            'employee_id' => $employee->id ?? null,
            'type' => 'alert',
            'title' => 'Email change approved',
            'message' => 'Your email has been changed from ' . $old . ' to ' . $user->email,
        ]);

        send_push_notification($user->id, 'Email changed', 'Your email change was approved by admin.', []);

        return response()->json(['message' => 'Request approved']);
    }

    // Deny request
    public function deny(Request $request, $id)
    {

        $req = ProfileChangeRequest::findOrFail($id);
        if ($req->status !== 'pending') {
            return response()->json(['message' => 'Request already processed'], 422);
        }

        $req->status = 'denied';
        $req->admin_id = Auth::id() ?: null;
        $req->admin_note = $request->input('note');
        $req->save();

        // Notify user
        Notification::create([
            'user_id' => $req->user_id,
            'employee_id' => Auth::id() ?: null,
            'type' => 'alert',
            'title' => 'Email change denied',
            'message' => 'Your email change request was denied. ' . ($req->admin_note ?? ''),
        ]);

        send_push_notification($req->user_id, 'Email change denied', 'Your email change was denied by admin.', []);

        return response()->json(['message' => 'Request denied']);
    }
}
