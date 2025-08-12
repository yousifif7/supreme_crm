<?php

namespace App\Http\Controllers\API;

use App\Models\BankDetails;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\EmergencyContacts;
use App\Http\Controllers\Controller;
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
                    'last_name' => $user->first_name,
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
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'phone' => $profile->phone,
            'address' => $profile->address,
            'emergency_contact' => $profile->emergencyContact ?? null,
            'bank_details' => $profile->bankDetail ?? null,
            'face_data' => $profile->face_data,
            'created_at' => $profile->created_at,
            'updated_at' => $profile->updated_at,
        ]);
    }


    public function updateProfile(Request $request)
    {
        $request->validate([
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

        $profile->update($request->only(['first_name', 'last_name', 'phone', 'address']));

        // Emergency Contact
        $emergency = $profile->emergencyContact ?: new EmergencyContacts(['profile_id' => $profile->id]);
        $emergency->fill($request->input('emergency_contact', []));
        $emergency->profile_id = $profile->id;
        $emergency->save();

        // Bank Details
        $bank = $profile->bankDetail ?: new BankDetails(['profile_id' => $profile->id]);
        $bank->fill($request->input('bank_details', []));
        $bank->profile_id = $profile->id;
        $bank->save();

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
