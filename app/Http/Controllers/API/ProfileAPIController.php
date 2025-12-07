<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Models\Employee;
use App\Models\BankDetails;
use Illuminate\Support\Str;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\EmergencyContacts;
use App\Http\Controllers\Controller;
use App\Models\ProfileChangeRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;


class ProfileAPIController extends Controller
{
    //

    public function getProfile(Request $request)
    {
        $user = $request->user()->load(['profile.bankDetail', 'profile.emergencyContact']);

        if (! $user->profile) {
            if (! $user->profile) {
                $user->profile()->create([
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'phone' => '',
                    'address' => '',
                    'emergency_contact' => [],
                    'bank_details' => [],
                    'face_data' => null,
                ]);
            }
        }

        $profile = $user->profile;

        return response()->json([
            'id' => $user->id,
            'email' => $user->email,
            'first_name' => $profile->first_name ?? '',
            'last_name' => $profile->last_name ?? '',
            'phone' => $profile->phone ?? '',
            'address' => $profile->address ??'',
            'emergency_contact' => $profile->emergencyContact ?? null,
            'bank_details' => $profile->bankDetail ?? null,
            'face_data' => $profile->face_data ??'',
            'created_at' => $profile->created_at ??'',
            'updated_at' => $profile->updated_at ?? '',
        ]);
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'email' => 'nullable|email',
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'emergency_contact.name' => 'nullable|string',
            'emergency_contact.phone' => 'nullable|string',
            'emergency_contact.relationship' => 'nullable|string',
            'bank_details.account_name' => 'nullable|string',
            'bank_details.account_number' => 'nullable|string',
            'bank_details.sort_code' => 'nullable|string',
            'bank_details.bank_name' => 'nullable|string',
        ]);

        $profile = $request->user()->profile;

        $user = $request->user();

        // If the request contains an email identical to current, proceed but suppress notifications
        $suppressNotifications = false;
        if ($request->filled('email') && $request->input('email') === $user->email) {
            $suppressNotifications = true;
        }

        // Handle email change requests specially: do not update directly
        if ($request->filled('email') && $request->input('email') !== $user->email) {
            // ensure requested email is not already taken
            if (User::where('email', $request->input('email'))->where('id', '<>', $user->id)->exists()) {
                return response()->json(['message' => 'Email already in use'], 422);
            }

            // Ensure fillable is set in Profile model
            $profile->fill($request->only(['first_name', 'last_name', 'phone', 'address']));
            $profile->save();

            // create a profile change request
            $req = ProfileChangeRequest::create([
                'user_id' => $user->id,
                'requested_email' => $request->input('email'),
                'old_email' => $user->email,
                'status' => 'pending',
            ]);

            // Notify admins/system about the request (link to the specific request)
            Notification::create([
                'user_id' => 1,
                'employee_id' => $user->id,
                'type' => 'alert',
                'title' => 'Profile Change Request',
                'message' => 'Guard ' . $user->first_name . ' ' . $user->last_name . ' requested profile changes (email).',
                'action_url' => '/admin/profile-change-requests/' . $req->id
            ]);

            return response()->json(['message' => 'Email change request submitted for admin approval']);
        }

        // Ensure fillable is set in Profile model
        $profile->fill($request->only(['first_name', 'last_name', 'phone', 'address']));
        $profile->save();


        $user1 = User::find(Auth::id());
        $user1->first_name=$profile->first_name;
        $user1->last_name=$profile->last_name;
        $user1->save();

        $employee = Employee::where('user_id', $request->user()->id)->first();
        $employee->fore_name = $profile->first_name;
        $employee->sur_name = $profile->last_name;
        $employee->save();

        // Emergency Contact
        $emergency = $profile->emergencyContact ?: new EmergencyContacts();
        $emergency->fill($request->input('emergency_contact', []));
        $emergency->profile_id = $profile->id;
        $emergency->save();

        // Bank Details
        $bank = $profile->bankDetail ?: new BankDetails();
        $bank->fill($request->input('bank_details', []));
        $bank->profile_id = $profile->id;
        $bank->save();


        if (! $suppressNotifications) {
            send_push_notification(
                Auth::id(),
                'Profile updated',
                'You have updated your profile successfully.',
                ['profile' => $profile],
            );
            
            $user = Auth::user();
            Notification::create([
                'user_id' => null,
                'employee_id' => Auth::id(),
                'type' => 'alert',
                'title' => 'Updated Profile',
                'message' => 'Guard '.$user->first_name.' '.$user->last_name.' has updated their profile.',
            ]);
        }

        return response()->json([
            'message' => 'Profile updated successfully',
            'approval_id' => uniqid('approval_')
        ]);
    }

    public function uploadFaceData(Request $request)
    {
        $request->validate([
            'images' => 'required|array|min:1',
            'images.*' => 'required|string', // base64 strings
        ]);

        $user = $request->user();
        $faceDataPaths = [];

        foreach ($request->images as $base64Image) {
            $image = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Image));
            $filename = 'faces/' . uniqid() . '.jpg';
            Storage::disk('public')->put($filename, $image);
            $faceDataPaths[] = $filename;
        }

        $user->profile()->update([
            'face_data' => json_encode($faceDataPaths),
        ]);

        return response()->json([
            'message' => 'Face data uploaded successfully.',
        ]);
    }
}
