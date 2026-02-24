<?php

namespace App\Traits;

use App\Helpers\Logger;
use App\Models\Log;
use Illuminate\Support\Facades\Auth;

trait LogsChanges
{
    /**
     * Resolve the current actor performing the change.
     * Prefer `request()->user()` (prevents stale Auth from long-running processes),
     * then fall back to `Auth::user()`. Returns null when action is system/console.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    private static function resolveActor()
    {
        try {
            if (function_exists('request')) {
                $req = request();
                if ($req && method_exists($req, 'user')) {
                    $u = $req->user();
                    if ($u) return $u;
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            if (\Illuminate\Support\Facades\Auth::check()) {
                return \Illuminate\Support\Facades\Auth::user();
            }
        } catch (\Throwable $_) {
            // ignore
        }

        return null;
    }

    public static function bootLogsChanges()
    {
        $arrayValues = [
            'absentee_start_time' => 'Book On Time',
            'absentee_end_time' => 'Book Off Time',
            'start_time' => 'Shift Start Time',
            'end_time' => 'Shift End Time',
            'staff_id' => 'Staff',
            'client_id' => 'Client',
            'site_id' => 'Site',
            'shift_date' => 'Shift Date',
            'total_hours' => 'Total hours',
            'status_id' => 'Status',
        ];

        static::updating(function ($model) use ($arrayValues) {
            $dirty = $model->getDirty();

            // Exclude unwanted fields
            unset($dirty['is_assign']);

            $fields = '';
            $labels = 'Updated ';

            foreach ($dirty as $field => $newValue) {
                $oldValue = $model->getOriginal($field);

                if ($field == 'staff_id') {
                    // Staff are stored as a user_id on shifts, but the human-readable
                    // name is maintained on the Employee record. Prefer Employee
                    // (by user_id) and fall back to the User record if needed.
                    $oldEmployee = \App\Models\Employee::where('user_id', $oldValue)->first();
                    $newEmployee = \App\Models\Employee::where('user_id', $newValue)->first();

                    $oldStaffName = $oldEmployee ? ($oldEmployee->fore_name . ' ' . $oldEmployee->sur_name) : null;
                    $newStaffName = $newEmployee ? ($newEmployee->fore_name . ' ' . $newEmployee->sur_name) : null;

                    if (!$oldStaffName && $oldValue) {
                        $oldUser = \App\Models\User::find($oldValue);
                        $oldStaffName = $oldUser ? ($oldUser->first_name . ' ' . $oldUser->last_name) : null;
                    }
                    if (!$newStaffName && $newValue) {
                        $newUser = \App\Models\User::find($newValue);
                        $newStaffName = $newUser ? ($newUser->first_name . ' ' . $newUser->last_name) : null;
                    }

                    // Determine if it's assign/unassign/reassign
                    if (!$oldValue && $newValue) {
                        $label = "Assigned to {$newStaffName}";
                    } elseif ($oldValue && !$newValue) {
                        $label = "Unassigned from {$oldStaffName}";
                    } elseif ($oldValue && $newValue) {
                        $label = "Reassigned from {$oldStaffName} to {$newStaffName}";
                    } else {
                        $label = "Staff";
                    }

                    $oldValue = $oldStaffName;
                    $newValue = $newStaffName;
                } elseif ($field == 'employee_id') {
                    // CheckCall.employee_id references an Employee record directly
                    $oldEmployee = $oldValue ? \App\Models\Employee::find($oldValue) : null;
                    $newEmployee = $newValue ? \App\Models\Employee::find($newValue) : null;

                    $oldEmployeeName = $oldEmployee ? ($oldEmployee->fore_name . ' ' . $oldEmployee->sur_name) : null;
                    $newEmployeeName = $newEmployee ? ($newEmployee->fore_name . ' ' . $newEmployee->sur_name) : null;

                    if (!$oldValue && $newValue) {
                        $label = "Assigned to {$newEmployeeName}";
                    } elseif ($oldValue && !$newValue) {
                        $label = "Unassigned from {$oldEmployeeName}";
                    } elseif ($oldValue && $newValue) {
                        $label = "Reassigned from {$oldEmployeeName} to {$newEmployeeName}";
                    } else {
                        $label = "Employee";
                    }

                    $oldValue = $oldEmployeeName;
                    $newValue = $newEmployeeName;
                } else {
                    $label = isset($arrayValues[$field]) ? $arrayValues[$field] : $field;
                }

                if ($field == 'status_id') {
                    $statuses = [
                        0 => 'pending',
                        1 => 'dispatched',
                        2 => 'accepted',
                        3 => 'started',
                        4 => 'ended',
                        5 => 'rejected',
                        6 => 'cancelled',
                        7 => 'pre-start',
                        8 => 'awaiting finish',
                    ];
                    $oldValue = $statuses[$oldValue] ?? $oldValue;
                    $newValue = $statuses[$newValue] ?? $newValue;
                }

                // Skip the duplicate label assignment for staff_id
                if ($field !== 'staff_id') {
                    if ($oldValue) {
                        if (is_array($oldValue)) {
                            $oldValue = json_encode($oldValue);
                        }
                        $label .= " from '{$oldValue}'";
                    }

                    if ($newValue) {
                        if (is_array($newValue)) {
                            $newValue = json_encode($newValue);
                        }
                        $label .= " to '{$newValue}'";
                    }
                }

                $fields .= (isset($arrayValues[$field]) ? $arrayValues[$field] : $field) . ', ';
                $labels .= $label . ', ';
            }

            $fields = rtrim($fields, ', ');
            $labels = rtrim($labels, ', ');

            // Add shift context for ShiftDate models
            $modelType = class_basename($model);
            if ($modelType == 'ShiftDate') {
                $site = $model->shift->site->site_name ?? 'Unknown Site';
                $date = $model->shift_date ?? 'N/A';
                $start = $model->start_time ?? 'N/A';
                $end = $model->end_time ?? 'N/A';
                
                $labels .= " for shift at {$site} on {$date}";
            }

            $actor = self::resolveActor();
            if ($actor) {
                $username = trim(($actor->first_name ?? '') . ' ' . ($actor->last_name ?? '')) ?: ($actor->name ?? 'Unknown');
            } else {
                $username = 'System';
            }

            $actionTitle = "Updated {$modelType}";
            // Do not include the username inside the description (we store it in the `user_name` column)
            $description = "{$actionTitle} {$fields}";

            // Add shift/site context when available
            if (isset($site) || isset($date)) {
                $siteLabel = $site ?? 'Unknown Site';
                $dateLabel = $date ?? 'N/A';
                $description .= " at {$siteLabel} on {$dateLabel}";
            }

            // Append the detailed diff/labels to resemble notification messages
            if (!empty($labels)) {
                $description .= ": {$labels}";
            }

            // If this is a Patrol, include the patrol name and due time for clearer context
            if ($modelType === 'Patrol') {
                $patrolName = $model->name ?? ($model->title ?? 'Unnamed Patrol');
                $patrolDue  = $model->start_time ? (' due ' . $model->start_time) : '';
                $description .= " (Patrol: {$patrolName}{$patrolDue})";
            }

            // If this is a CheckCall, include the checkcall name and scheduled time
            if ($modelType === 'CheckCall') {
                $ccName = $model->name ?? 'Unnamed CheckCall';
                $ccDue  = $model->scheduled_time ? (' due ' . $model->scheduled_time) : '';
                $description .= " (CheckCall: {$ccName}{$ccDue})";
            }

            // Use central Logger helper so all logs follow the same format/resolution
            try {
                Logger::log($model, $actionTitle, $description);
            } catch (\Throwable $_) {
                // fallback: create directly if Logger fails
                try {
                    $model->logs()->create([
                        'user_name' => $username,
                        'action' => $actionTitle,
                        'description' => $description,
                    ]);
                } catch (\Throwable $__) {
                    // swallow to avoid breaking save
                }
            }
        });

        static::created(function ($model) {
            $modelType = class_basename($model); // e.g., "Client" or "Site"

            // Try to get a name field, or fallback to ID
            $label = $model->client_name ?? $model->site_name ?? $model->fore_name ?? $model->shift->fore_name ?? $model->company_name ?? $model->first_name ?? $model->name ?? $model->id;

            // create shift logs description
                if ($modelType == 'ShiftDate') {
                $modelType = 'Shift';
                // Prefer Employee name (fore_name/sur_name) by user_id, fall back to User
                $staff = 'Unassigned';
                if (!empty($model->staff_id)) {
                    $emp = \App\Models\Employee::where('user_id', $model->staff_id)->first();
                    if ($emp) {
                        $staff = $emp->fore_name . ' ' . $emp->sur_name;
                    } elseif (isset($model->staff->first_name)) {
                        $staff = $model->staff->first_name . ' ' . $model->staff->last_name;
                    }
                }

                $site = $model->shift->site->site_name ?? 'Unknown Site';
                $date = $model->shift_date ?? 'N/A';
                $start = $model->start_time ?? 'N/A';
                $end = $model->end_time ?? 'N/A';

                $label = "at {$site} on {$date}";
                if ($model->staff_id) {
                    $label .= " (Assigned to {$staff})";
                } else {
                    $label .= " (Unassigned)";
                }
            }

            $actor = self::resolveActor();
            if ($actor) {
                $username = trim(($actor->first_name ?? '') . ' ' . ($actor->last_name ?? '')) ?: ($actor->name ?? 'Unknown');
            } else {
                $username = 'System';
            }

            $actionTitle = "Created {$modelType}";
            // Do not include the username inside the description (we store it in the `user_name` column)
            $description = "{$actionTitle} {$label}";

            // If this is a Patrol, include the patrol name and due time for clearer context
            if ($modelType === 'Patrol') {
                $patrolName = $model->name ?? ($model->title ?? 'Unnamed Patrol');
                $patrolDue  = $model->start_time ? (' due ' . $model->start_time) : '';
                $description .= " (Patrol: {$patrolName}{$patrolDue})";
            }

            // If this is a CheckCall, include the checkcall name and scheduled time
            if ($modelType === 'CheckCall') {
                $ccName = $model->name ?? 'Unnamed CheckCall';
                $ccDue  = $model->scheduled_time ? (' due ' . $model->scheduled_time) : '';
                $description .= " (CheckCall: {$ccName}{$ccDue})";
            }

            // Use central Logger helper so all logs follow the same format/resolution
            try {
                Logger::log($model, $actionTitle, $description);
            } catch (\Throwable $_) {
                try {
                    $model->logs()->create([
                        'user_name' => $username,
                        'action' => $actionTitle,
                        'description' => $description,
                    ]);
                } catch (\Throwable $__) {
                    // swallow
                }
            }
        });
    }

    public function logs()
    {
        return $this->morphMany(Log::class, 'loggable')->orderBy('created_at', 'desc');
    }
}
