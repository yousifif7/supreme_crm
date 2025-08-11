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


function applyRestrictions($entity, $validator, $fieldName = 'staff_id', $newShiftHours = 0, $shiftDate = null)
{
    $entityClass = get_class($entity);
    $restrictions = \App\Models\Restriction::where('entity_type', $entityClass)
        ->where('is_active', true)
        ->get();

    $missingDocuments = [];

    foreach ($restrictions as $restriction) {
        $field = $restriction->field_name;
        $message = $restriction->error_message;

        switch ($restriction->restriction_type) {
            case 'expiry_check':
                if ($entity->$field && \Carbon\Carbon::parse($entity->$field)->lt(now())) {
                    $validator->errors()->add($fieldName, $message);
                }
                break;

            case 'required_field_check':
                if (empty($entity->$field)) {
                    $validator->errors()->add($fieldName, $message);
                }
                break;

            case 'document_check':
                if (empty($entity->$field)) {
                    $missingDocuments[] = $message;
                }
                break;

            case 'max_weekly_hours_check':
                $weekStart = now()->startOfWeek();
                $weekEnd = now()->endOfWeek();

                $totalWeekHours = \App\Models\ShiftDate::where('staff_id', $entity->id)
                    ->whereBetween('shift_date', [$weekStart, $weekEnd])
                    ->sum('total_hours');

                $maxWeeklyHours = $entity->hour_per_week ?? 40;

                if (($totalWeekHours + $newShiftHours) > $maxWeeklyHours) {
                    $validator->errors()->add($fieldName, $message);
                }
                break;

            case 'student_visa_hours_check':
                if ($entity->visa_type === 'Student' && $shiftDate) {
                    $isShiftInActiveTerm = \App\Models\EmployeeTerm::where('employee_id', $entity->id)
                        ->where(function ($query) use ($shiftDate) {
                            $query->where('from_date', '<=', $shiftDate)
                                ->where('to_date', '>=', $shiftDate);
                        })
                        ->exists();

                    if (!$isShiftInActiveTerm) {
                        $weeklyHours = \App\Models\ShiftDate::where('staff_id', $entity->id)
                            ->whereBetween('shift_date', [now()->startOfWeek(), now()->endOfWeek()])
                            ->sum('total_hours') + $newShiftHours;

                        if ($weeklyHours > 20) {
                            $validator->errors()->add($fieldName, $message);
                        }
                    }
                }
                break;
        }
    }

    if (!empty($missingDocuments)) {
        $validator->errors()->add($fieldName, "Missing required documents: " . implode(', ', $missingDocuments));
    }
}


if (!function_exists('send_push_notification')) {
    function send_push_notification($userId, $title, $message, $data = [])
    {
        // Fetch all device tokens for the given user
        $devices = \App\Models\DeviceToken::where('user_id', $userId)
            ->whereNotNull('push_token')
            ->pluck('push_token')
            ->toArray();

        if (empty($devices)) {
            \Log::info("No device tokens found for user ID: {$userId}");
            return false;
        }

        try {
            $driver = new \ExponentPhpSDK\Repositories\ExpoFileDriver();
            $registrar = new \ExponentPhpSDK\ExpoRegistrar($driver);
            $expo = new \ExponentPhpSDK\Expo($registrar);

            $expo->setAccessToken('wz2xtEKGkvW7qTc_uUVVaefX-M2E1vilwavavQzw');

            // Send the notification directly to all device tokens
            $expo->notify($devices, [
                'title' => $title,
                'body' => $message,
                'sound' => 'default',
                'data' => $data,
            ]);

            // Save notification in DB for tracking
            \App\Models\Notification::create([
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'data' => $data,
                'type' => $data['type'] ?? 'notification',
                'read' => false,
                'action_url' => $data['action_url'] ?? null,
            ]);

            return true;

        } catch (\Throwable $e) {
            \Log::error("Push notification error for user ID {$userId}: {$e->getMessage()}");
            return false;
        }
    }
}




class Notify
{
    public static function toDashboard($employeeId, $type, $title, $message, $action_url = false)
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
