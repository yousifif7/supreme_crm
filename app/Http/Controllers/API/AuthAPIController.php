<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Mail\ResetPasswordCodeMail;
use App\Models\DeviceChangeRequest;
use App\Models\DeviceLog;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;


class AuthAPIController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'location.latitude' => 'nullable|numeric',
            'location.longitude' => 'nullable|numeric',
            'device_info.device_id' => 'required|string',
            'device_info.device_name' => 'nullable|string',
            'device_info.os' => 'nullable|string',
            'device_info.app_version' => 'nullable|string',
        ]);

        // Normalize inputs to avoid issues with leading/trailing spaces or case differences
        $email = trim(mb_strtolower((string) $request->email));
        $password = trim((string) $request->password);

        // Find user case-insensitively
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            // Log failed attempt for debugging (do NOT log plaintext password)
            try {
                Log::channel('auth_attempts')->warning('Login failed', [
                    'email' => $request->email,
                    'email_normalized' => $email,
                    'device_id' => $request->input('device_info.device_id'),
                    'ip' => $request->ip(),
                    'user_found' => $user ? true : false,
                ]);
            } catch (\Exception $e) {
                // ignore logging failures
            }

            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // For security staff, validate device
        if ($user->hasRole('security_staff')) {
            $lastDeviceLog = $user->deviceLogs()->latest()->first();
            
            // Skip validation if this is first login or device_id is empty (old app version)
            if (!$lastDeviceLog || empty($request->device_info['device_id'])) {
                // Log device info
                $user->deviceLogs()->create([
                    'device_id' => $request->device_info['device_id'] ?? 'legacy_device',
                    'device_name' => $request->device_info['device_name'] ?? null,
                    'os' => $request->device_info['os'] ?? null,
                    'app_version' => $request->device_info['app_version'] ?? null,
                    'latitude' => $request->location['latitude'] ?? null,
                    'longitude' => $request->location['longitude'] ?? null,
                ]);
            }
            // If device changed
            elseif ($lastDeviceLog->device_id !== $request->device_info['device_id']) {
                // If there is already a pending request for this same new device, do not create a duplicate
                $existingPendingRequest = $user->deviceChangeRequests()
                    ->where('new_device_id', $request->device_info['device_id'])
                    ->where('status', 'pending')
                    ->exists();

                if ($existingPendingRequest) {
                    return response()->json([
                        'message' => 'Your previous device change request is still pending admin approval. Please check back later.',
                        'requires_approval' => true,
                        'already_requested' => true
                    ], 403);
                }

                $user->deviceChangeRequests()->create([
                    'old_device_id' => $lastDeviceLog->device_id,
                    'new_device_id' => $request->device_info['device_id'],
                    'new_device_name' => $request->device_info['device_name'] ?? null,
                    'new_os' => $request->device_info['os'] ?? null,
                    'new_app_version' => $request->device_info['app_version'] ?? null,
                    'status' => 'pending'
                ]);


            Notification::create([
                'user_id' => 1,
                'employee_id' => null,
                'type' => 'alert',
                'title' => 'Mobile Change Detected',
                'message' => 'Guard ' . $user->first_name . ' ' . $user->last_name . ' has logged in from a new device. Please review and approve the change.',
                'read' => false,
                'action_url' => ""
            ]);

                return response()->json([
                    'message' => 'Device change detected. Admin approval required. You can close the app and check back later.',
                    'requires_approval' => true
                ], 403);
            }
        }

        // Log device info
        $user->deviceLogs()->create([
            'device_id' => $request->device_info['device_id'],
            'device_name' => $request->device_info['device_name'] ?? null,
            'os' => $request->device_info['os'] ?? null,
            'app_version' => $request->device_info['app_version'] ?? null,
            'latitude' => $request->location['latitude'] ?? null,
            'longitude' => $request->location['longitude'] ?? null,
        ]);

        // Create access token
        $accessToken = $user->createToken('access_token')->plainTextToken;

        // Generate a refresh token manually (you’ll store this)
        $refreshToken = Str::random(60);
        Cache::put('refresh_token_' . $refreshToken, $user->id, now()->addDays(30));

        return response()->json([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->first_name . ' ' . $user->last_name,
                'role' => $user->getRoleNames()->first(),
                'profile' => $user->profile // make sure profile relation is defined
            ],
        ]);
    }


    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();
        if (! $user) {
            return response()->json(['message' => 'This email is not existed in our records.'], 200);
        }

        $code = rand(100000, 999999);
        Cache::put('reset_code_' . $request->email, $code, now()->addMinutes(15));

        DB::table('password_resets')->updateOrInsert(
            ['email' => $user->email],
            ['code' => $code, 'created_at' => now()]
        );

        // ✅ Send via Hostinger SMTP
        Mail::to($user->email)->send(new ResetPasswordCodeMail($code));

        send_push_notification(
            $user->id,
            'PASSWORD Reset code',
            'Password code reset was requested from your account! Check your email.',
            ['type' => 'profile']
        );

        return response()->json([
            'message' => 'Reset code sent to your email.'
        ], 200);
    }

    public function verifyResetCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string'
        ]);

        $cachedCode = DB::table('password_resets')->where('email', $request->email)->value('code');
        // Cache::get('reset_code_' . $request->email);

        if (!$cachedCode || (string) $cachedCode !== (string) $request->code) {
            return response()->json([
                'message' => 'Invalid or expired code.'
            ], 422);
        }

        // Generate a secure token to allow password reset
        $resetToken = Str::random(64);
        Cache::put('reset_token_' . $request->email, $resetToken, now()->addMinutes(30));
        return response()->json([
            'message' => 'Code verified',
            'reset_token' => $resetToken
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'reset_token' => 'required|string',
            'new_password' => 'required|string|min:6',
        ]);

        $cachedToken = Cache::get('reset_token_' . $request->email);

        if (!$cachedToken || $cachedToken !== $request->reset_token) {
            return response()->json([
                'message' => 'Invalid or expired reset token'
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        Log::info('Before saving user password reset: ' . $user->id);
        $user->plaintext_password = $request->new_password;
        $user->password = bcrypt($request->new_password);
        $user->save();
        Log::info('After saving user password reset');

        Cache::forget('reset_token_' . $request->email);
        DB::table('password_resets')->where('email', $request->email)->delete();

        send_push_notification(
            $user->id,
            'PASSWORD changed successfully',
            'Your account password has been changed successfully!.',
            ['type' => 'profile']
        );

        return response()->json([
            'message' => 'Password reset successfully'
        ]);
    }

    public function faceVerify(Request $request)
    {
        $request->validate([
            'image' => 'required|string',
            'action' => 'required|in:book_on,book_off',
            'location.latitude' => 'nullable|numeric',
            'location.longitude' => 'nullable|numeric',
            'location.address' => 'nullable|string',
        ]);

        $user = $request->user();

        // Simulate confidence score
        $confidence = rand(85, 99) + (rand(0, 10) / 10);

        // Here you can integrate with real face recognition later
        $verified = $confidence >= 85;

        return response()->json([
            'verified' => $verified,
            'confidence_score' => $confidence,
            'message' => $verified ? 'Face verified successfully' : 'Face verification failed'
        ]);
    }

    public function refreshToken(Request $request)
    {
        $request->validate([
            'refresh_token' => 'required|string',
        ]);

        // Use the token string to find the user ID
        $userId = Cache::get('refresh_token_' . $request->refresh_token);

        if (! $userId) {
            return response()->json(['message' => 'Invalid refresh token'], 401);
        }

        $user = User::find($userId);

        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Create new tokens
        $accessToken = $user->createToken('access_token')->plainTextToken;
        $newRefreshToken = Str::random(60);

        // Replace the old token
        Cache::forget('refresh_token_' . $request->refresh_token);
        Cache::put('refresh_token_' . $newRefreshToken, $user->id, now()->addDays(30));

        return response()->json([
            'access_token' => $accessToken,
            'refresh_token' => $newRefreshToken,
        ]);
    }

    public function approveDeviceChange(Request $request)
    {
        $request->validate([
            'request_id' => 'required|exists:device_change_requests,id',
            'action' => 'required|in:approve,reject',
            'note' => 'nullable|string'
        ]);

        $changeRequest = DeviceChangeRequest::find($request->request_id);
        
        if ($changeRequest->status !== 'pending') {
            return response()->json(['message' => 'Request has already been processed'], 400);
        }

        if ($request->action === 'approve') {
            // Delete old device logs
            DeviceLog::where('user_id', $changeRequest->user_id)
                ->where('device_id', $changeRequest->old_device_id)
                ->delete();
                
            $changeRequest->update([
                'status' => 'approved',
                'admin_id' => $request->user()->id,
                'admin_note' => $request->note,
                'approved_at' => now()
            ]);

            send_push_notification(
                $changeRequest->user_id,
                'Device change Approved',
                'Your Device change request was approved by Admins! You can login to the new device now.',
                ['type' => 'profile']
            );
            
            return response()->json(['message' => 'Device change approved successfully']);
        } else {
            $changeRequest->update([
                'status' => 'rejected',
                'admin_id' => $request->user()->id,
                'admin_note' => $request->note,
                'rejected_at' => now()
            ]);
            
            send_push_notification(
                $changeRequest->user_id,
                'Device change Denied',
                'Your Device change request was denied by Admins! If this is truly you, Contact with an Administrator to resolve the issue.',
                ['type' => 'profile']
            );

            return response()->json(['message' => 'Device change rejected']);
        }
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'device_info.device_id' => 'nullable|string',
            'refresh_token' => 'nullable|string',
        ]);

        $deviceId = $request->input('device_info.device_id');
        $refreshToken = $request->input('refresh_token');

        // Delete current access token (sanctum)
        try {
            if ($request->user() && method_exists($request->user(), 'currentAccessToken')) {
                $token = $request->user()->currentAccessToken();
                if ($token) $token->delete();
            }
        } catch (\Exception $e) {
            try { Log::channel('auth_attempts')->error('Failed to delete access token on logout', ['user_id' => $user->id, 'error' => $e->getMessage()]); } catch (\Exception $_) {}
        }

        // Remove refresh token from cache if provided
        if ($refreshToken) {
            try { Cache::forget('refresh_token_' . $refreshToken); } catch (\Exception $e) {}
        }

        // Remove device log entry for this device to stop pushes for this device
        if ($deviceId) {
            try {
                // For security staff, also reject any pending device change requests
                if ($user->hasRole('security_staff')) {
                    DeviceChangeRequest::where('user_id', $user->id)
                        ->where('new_device_id', $deviceId)
                        ->where('status', 'pending')
                        ->update([
                            'status' => 'rejected',
                            'rejected_at' => now(),
                            'admin_note' => 'Automatically rejected due to logout'
                        ]);
                }
                
                DeviceLog::where('user_id', $user->id)->where('device_id', $deviceId)->delete();
            } catch (\Exception $e) {
                try { Log::channel('auth_attempts')->error('Failed to remove device log on logout', ['user_id' => $user->id, 'device_id' => $deviceId, 'error' => $e->getMessage()]); } catch (\Exception $_) {}
            }
        }

        try {
            Log::channel('auth_attempts')->info('User logged out', ['user_id' => $user->id, 'device_id' => $deviceId ?? null]);
        } catch (\Exception $e) {}

        return response()->json(['message' => 'Logged out successfully'], 200);
    }
}
