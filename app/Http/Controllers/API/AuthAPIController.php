<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Mail\ResetPasswordCodeMail;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;


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

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
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
}
