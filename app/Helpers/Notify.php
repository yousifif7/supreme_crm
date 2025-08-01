<?php

use App\Models\Notification;
use App\Models\User;
use App\Models\Restriction;
use Carbon\Carbon;


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



function applyRestrictions($entity, $validator, $fieldName = 'staff_id')
{
    $entityClass = get_class($entity);
    $restrictions = \App\Models\Restriction::where('entity_type', $entityClass)
                                           ->where('is_active', true)
                                           ->get();

    $missingDocuments = [];

    foreach ($restrictions as $restriction) {
        $field = $restriction->field_name;
        $message = $restriction->error_message;

        if ($restriction->restriction_type === 'expiry_check') {
            if ($entity->$field && \Carbon\Carbon::parse($entity->$field)->lt(now())) {
                $validator->errors()->add($fieldName, $message);
            }
        }

        if ($restriction->restriction_type === 'required_field_check') {
            if (empty($entity->$field)) {
                $validator->errors()->add($fieldName, $message);
            }
        }

        if ($restriction->restriction_type === 'document_check') {
            if (empty($entity->$field)) {
                $missingDocuments[] = $message;
            }
        }
    }

    if (!empty($missingDocuments)) {
        $validator->errors()->add($fieldName, "Missing required documents: " . implode(', ', $missingDocuments));
    }
}



class Notify
{
    public static function toDashboard($employeeId, $type, $title, $message)
    {
        // Assuming user ID 1 is the dashboard user
        Notification::create([
            'user_id' => 1,
            'employee_id' => $employeeId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'read' => false,
        ]);
    }
}