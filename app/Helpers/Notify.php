<?php

use App\Models\User;
use ExponentPhpSDK\Expo;
use App\Models\ShiftDate;
use App\Models\DeviceToken;
use App\Models\Availability;
use App\Models\Notification;
use ExponentPhpSDK\ExpoRegistrar;
use Illuminate\Support\Facades\Http;
use ExponentPhpSDK\Repositories\ExpoFileDriver;
use App\Models\Log;

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

        if (!function_exists('combine_date_time')) {
            function combine_date_time($dateVal, $timeVal)
            {
                try {
                    $dateOnly = \Carbon\Carbon::parse($dateVal)->toDateString();
                } catch (\Exception $e) {
                    $dateOnly = (string) $dateVal;
                }

                return \Carbon\Carbon::parse(trim($dateOnly . ' ' . $timeVal));
            }
        }
}


function applyRestrictions($entity, $validator, $fieldName = 'staff_id', $newShiftHours = 0, $shiftDate = null, $newShiftStart = null)
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

            // case 'max_weekly_hours_check':
            //     $weekStart = now()->startOfWeek();
            //     $weekEnd = now()->endOfWeek();

            //     $totalWeekHours = \App\Models\ShiftDate::where('staff_id', $entity->user_id) // FIXED
            //         ->whereBetween('shift_date', [$weekStart, $weekEnd])
            //         ->sum('total_hours');

            //     $maxWeeklyHours = $entity->hour_per_week ?? 40;

            //     if (($totalWeekHours + $newShiftHours) > $maxWeeklyHours) {
            //         $validator->errors()->add($fieldName, $message);
            //     }
            //     break;

            case 'student_visa_hours_check':
                if (strtolower($entity->visa_type) === 'student' && $shiftDate) {
                    $isShiftInActiveTerm = \App\Models\EmployeeTerm::where('employee_id', $entity->id)
                        ->where(function ($query) use ($shiftDate) {
                            $query->where('from_date', '<=', $shiftDate)
                                ->where('to_date', '>=', $shiftDate);
                        })
                        ->exists();

                    // If the shift falls inside an active student term, allow it (no 20h weekly cap).
                    // Otherwise apply the 20-hour weekly restriction for the week that contains the shift date.
                    if (!$isShiftInActiveTerm) {
                        try {
                            $shiftCarbon = \Carbon\Carbon::parse($shiftDate);
                            $weekStart = $shiftCarbon->copy()->startOfWeek();
                            $weekEnd = $shiftCarbon->copy()->endOfWeek();

                            $weeklyHours = \App\Models\ShiftDate::where('staff_id', $entity->user_id)
                                ->whereBetween('shift_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                                ->sum('total_hours');

                            $weeklyHours = ($weeklyHours ?: 0) + (is_numeric($newShiftHours) ? (float) $newShiftHours : 0);

                            if ($weeklyHours > 20) {
                                $validator->errors()->add($fieldName, $message);
                            }
                        } catch (\Exception $e) {
                            // If parsing fails, conservatively apply the restriction using current week
                            $weeklyHours = \App\Models\ShiftDate::where('staff_id', $entity->user_id)
                                ->whereBetween('shift_date', [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()])
                                ->sum('total_hours') + (is_numeric($newShiftHours) ? (float) $newShiftHours : 0);

                            if ($weeklyHours > 20) {
                                $validator->errors()->add($fieldName, $message);
                            }
                        }
                    }
                }
                break;
            case 'min_rest_hours_check':
                if ($newShiftStart instanceof \Carbon\Carbon) {
                    $lastShift = \App\Models\ShiftDate::where('staff_id', $entity->user_id)
                        ->whereNotNull('end_time')
                        ->orderByDesc('shift_date')
                        ->orderByDesc('end_time')
                        ->first();

                    if ($lastShift) {
                        // Build last shift start and end
                        $lastShiftStart = combine_date_time($lastShift->shift_date, $lastShift->start_time);
                        $lastShiftEnd   = combine_date_time($lastShift->shift_date, $lastShift->end_time);

                        // If shift crosses midnight (end <= start), add a day
                        if ($lastShiftEnd->lte($lastShiftStart)) {
                            $lastShiftEnd->addDay();
                        }

                        // Now compare only if lastShiftEnd is before the new shift start
                        if ($lastShiftEnd->lte($newShiftStart)) {
                            $hoursDiff = $lastShiftEnd->diffInHours($newShiftStart);

                            \Log::info('12h check result', [
                                'lastShiftEnd' => $lastShiftEnd->toDateTimeString(),
                                'newShiftStart' => $newShiftStart->toDateTimeString(),
                                'hoursDiff' => $hoursDiff,
                            ]);

                            if ($hoursDiff < 12) {
                                $validator->errors()->add(
                                    $fieldName,
                                    $message ?: 'Staff must have at least 12 hours rest between shifts.'
                                );
                            }
                        }
                    }
                }
            case 'staff_availability_hours':
                if ($shiftDate && $newShiftStart && $newShiftHours > 0) {
                    // Get day of week from shift date (0=Sunday ... 6=Saturday)
                    $dayOfWeek = \Carbon\Carbon::parse($shiftDate)->dayOfWeek;

                    $availability = \App\Models\Availability::where('user_id', $entity->user_id)
                        ->where('day_of_week', $dayOfWeek)
                        ->first();

                    if (!$availability) {
                        // No availability set for this day
                        $validator->errors()->add(
                            $fieldName,
                            $restriction->error_message ?? 'No availability set for this day.'
                        );
                    } else {
                        $shiftHours = is_numeric($newShiftHours) ? (float) $newShiftHours : 0;
                        $shiftEnd = $newShiftStart->copy()->addHours($shiftHours);

                        $availableStart = combine_date_time($shiftDate, $availability->start_time);
                        $availableEnd   = combine_date_time($shiftDate, $availability->end_time);

                        // Handle overnight availability (end <= start)
                        if ($availableEnd->lte($availableStart)) {
                            $availableEnd->addDay();
                        }

                        // Shift must fully fit within availability
                        if ($newShiftStart->lt($availableStart) || $shiftEnd->gt($availableEnd)) {
                            $validator->errors()->add(
                                $fieldName,
                                $restriction->error_message ?? 'Shift falls outside the staff availability hours.'
                            );
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

function send_push_notification($userId, $title, $message, $data = [])
{
    $devices = \App\Models\DeviceToken::where('user_id', $userId)
        ->whereNotNull('push_token')
        ->pluck('push_token')
        ->toArray();

    if (empty($devices)) {
        \Log::info("No device tokens found for user ID: {$userId}");
        return false;
    }

    foreach ($devices as $token) {
        $payload = [
            "to" => $token,
            "sound" => "default",
            "title" => $title,
            "body" => $message,
            "data" => json_decode(json_encode($data), true), // always object
        ];

        \Log::info("Push Notification: Sending payload to Expo", [
            "user_id" => $userId,
            "token"   => $token,
            "payload" => $payload
        ]);

        try {
            $ch = curl_init("https://exp.host/--/api/v2/push/send");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: application/json",
                "Accept: application/json",
                "Authorization: Bearer " . env("EXPO_ACCESS_TOKEN"),
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

            $response = curl_exec($ch);
            $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($status !== 200) {
                \Log::error("Push Notification: Expo returned error", [
                    "user_id" => $userId,
                    "token"   => $token,
                    "status"  => $status,
                    "body"    => $response
                ]);
            } else {
                $responseData = json_decode($response, true);
                
                \Log::info("Push Notification: Successfully sent", [
                    "user_id" => $userId,
                    "token"   => $token,
                    "response" => $responseData
                ]);
                
                // Check if Expo reports this token as invalid
                if (isset($responseData['data'][0]['status']) && $responseData['data'][0]['status'] === 'error') {
                    $errorDetails = $responseData['data'][0]['details'] ?? [];
                    $errorType = $errorDetails['error'] ?? '';
                    
                    // Remove invalid tokens (DeviceNotRegistered means token expired or app uninstalled)
                    if (in_array($errorType, ['DeviceNotRegistered', 'InvalidCredentials', 'MessageTooBig', 'MessageRateExceeded'])) {
                        \Log::warning("Push Notification: Removing invalid token", [
                            "user_id" => $userId,
                            "token"   => $token,
                            "error"   => $errorType
                        ]);
                        
                        \App\Models\DeviceToken::where('push_token', $token)->delete();
                    }
                }
            }
        } catch (\Throwable $e) {
            \Log::error("Push Notification: Exception while sending", [
                "user_id" => $userId,
                "token"   => $token,
                "error"   => $e->getMessage()
            ]);
        }
    }

    // Save notification in DB once, outside the loop
    Notification::create([
        'user_id' => $userId,
        'employee_id' => null,
        'type' => 'alert',
        'title' => $title,
        'message' => $message,
    ]);

    return true;
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

