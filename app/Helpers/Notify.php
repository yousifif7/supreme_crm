<?php

use App\Models\User;
use ExponentPhpSDK\Expo;
use App\Models\DeviceToken;
use App\Models\Notification;
use ExponentPhpSDK\Repositories\ExpoFileDriver;
use ExponentPhpSDK\ExpoRegistrar;

if (!function_exists('notify_users')) {
    function notify_users($title, $message, $type = 'notification', $action_url = null, $data = [], $users = null)
    {
        // If no specific users passed, notify everyone
        $users = $users ?? User::all();

        foreach ($users as $user) {
            Notification::create([
                'user_id' => $user->id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'read' => false,
                'data' => $data,
                'action_url' => $action_url,
            ]);
        }
    }
}

if (!function_exists('send_push_notification')) {
    function send_push_notification($employeeId, $title, $message, $data = [])
    {
        $device = DeviceToken::where('employee_id', $employeeId)->first();

        if (!$device || !$device->push_token) {
            \Log::info("No device token found for employee ID: $employeeId");
            return false;
        }

        try {
            $driver = new ExpoFileDriver(); // This handles file storage
            $registrar = new ExpoRegistrar($driver); // This wraps it properly
            $expo = new Expo($registrar); // Now you're passing the correct type
            $expo->setAccessToken('wz2xtEKGkvW7qTc_uUVVaefX-M2E1vilwavavQzw');
            $expo->subscribe($employeeId, $device->push_token);
            $expo->notify([$employeeId], [
                'title' => $title,
                'body' => $message,
                'sound' => 'default',
                'data' => $data,
            ]);
            return true;
        } catch (\Exception $e) {
            \Log::error("Push notification error: " . $e->getMessage());
            return false;
        }
    }
}

class Notify
{
    public static function toDashboard($employeeId, $type, $title, $message, $action_url)
    {
        // Assuming user ID 1 is the dashboard user
        Notification::create([
            'user_id' => 1,
            'employee_id' => $employeeId,
            'action_url' => $action_url,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'read' => false,
        ]);
    }
}
