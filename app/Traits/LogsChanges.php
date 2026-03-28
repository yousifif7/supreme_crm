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
            
            // Exclude rate/numeric field updates when values are effectively unchanged (e.g., 0.00 to 0, null to empty)
            $numericFields = ['guard_rate', 'site_rate', 'employee_rate', 'total_hours'];
            foreach ($numericFields as $field) {
                if (isset($dirty[$field])) {
                    $oldValue = (float) ($model->getOriginal($field) ?? 0);
                    $newValue = (float) ($dirty[$field] ?? 0);
                    if (abs($oldValue - $newValue) < 0.01) {
                        unset($dirty[$field]);
                    }
                }
            }
            
            // If no meaningful changes remain, skip logging
            if (empty($dirty)) {
                return;
            }

            $fields = '';
            $labels = 'Updated ';

            foreach ($dirty as $field => $newValue) {
                $oldValue = $model->getOriginal($field);

                // Set a default label for each field to avoid undefined variable error
                $label = ucfirst(str_replace('_', ' ', $field));

                if ($field == 'staff_id') {
                    // Staff are stored as a user_id on shifts, use User model name fields only
                    $oldUser = $oldValue ? \App\Models\User::find($oldValue) : null;
                    $newUser = $newValue ? \App\Models\User::find($newValue) : null;

                    $oldStaffName = $oldUser ? (trim((($oldUser->first_name ?? '') . ' ' . ($oldUser->last_name ?? '')))) : null;
                    $newStaffName = $newUser ? (trim((($newUser->first_name ?? '') . ' ' . ($newUser->last_name ?? '')))) : null;

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
                } elseif ($field == 'subcontractor' || $field == 'subcontractor_id') {
                    // Handle both array of IDs and single ID
                    if (is_array($oldValue) || is_array($newValue)) {
                        // Array logic for multiple subcontractors
                        $oldSubs = is_array($oldValue) ? $oldValue : [];
                        $newSubs = is_array($newValue) ? $newValue : [];

                        $oldSubNames = [];
                        $newSubNames = [];

                        if (!empty($oldSubs)) {
                            $oldUsers = \App\Models\User::whereIn('id', $oldSubs)->get();
                            $oldSubNames = $oldUsers->map(fn($u) => trim($u->first_name . ' ' . $u->last_name))->filter()->values()->toArray();
                        }

                        if (!empty($newSubs)) {
                            $newUsers = \App\Models\User::whereIn('id', $newSubs)->get();
                            $newSubNames = $newUsers->map(fn($u) => trim($u->first_name . ' ' . $u->last_name))->filter()->values()->toArray();
                        }

                        $oldSubList = implode(', ', $oldSubNames);
                        $newSubList = implode(', ', $newSubNames);

                        if (empty($oldSubs) && !empty($newSubs)) {
                            $label = "Assigned subcontractors: {$newSubList}";
                        } elseif (!empty($oldSubs) && empty($newSubs)) {
                            $label = "Removed subcontractors: {$oldSubList}";
                        } elseif (!empty($oldSubs) && !empty($newSubs)) {
                            $added = array_diff($newSubs, $oldSubs);
                            $removed = array_diff($oldSubs, $newSubs);

                            $changes = [];
                            if (!empty($added)) {
                                $addedUsers = \App\Models\User::whereIn('id', $added)->get();
                                $addedNames = $addedUsers->map(fn($u) => trim($u->first_name . ' ' . $u->last_name))->filter()->implode(', ');
                                $changes[] = "Added: {$addedNames}";
                            }
                            if (!empty($removed)) {
                                $removedUsers = \App\Models\User::whereIn('id', $removed)->get();
                                $removedNames = $removedUsers->map(fn($u) => trim($u->first_name . ' ' . $u->last_name))->filter()->implode(', ');
                                $changes[] = "Removed: {$removedNames}";
                            }
                            $label = "Subcontractors updated: " . implode('; ', $changes);
                        } else {
                            $label = "Subcontractor";
                        }

                        $oldValue = $oldSubList;
                        $newValue = $newSubList;
                    } else {
                        // Single subcontractor ID logic
                        $oldSubName = null;
                        if ($oldValue) {
                            $oldUser = \App\Models\User::find($oldValue);
                            $oldSubName = $oldUser ? trim($oldUser->first_name . ' ' . $oldUser->last_name) : 'Unknown';
                        }

                        $newSubName = null;
                        if ($newValue) {
                            $newUser = \App\Models\User::find($newValue);
                            $newSubName = $newUser ? trim($newUser->first_name . ' ' . $newUser->last_name) : 'Unknown';
                        }

                        if (!$oldValue && $newValue) {
                            $label = "Assigned subcontractor: {$newSubName}";
                        } elseif ($oldValue && !$newValue) {
                            $label = "Removed subcontractor: {$oldSubName}";
                        } elseif ($oldValue && $newValue) {
                            $label = "Changed subcontractor from {$oldSubName} to {$newSubName}";
                        } else {
                            $label = "Subcontractor";
                        }

                        $oldValue = $oldSubName;
                        $newValue = $newSubName;
                    }
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

                // Skip the duplicate label assignment for staff_id, subcontractor, and subcontractor_id
                if ($field !== 'staff_id' && $field !== 'subcontractor' && $field !== 'subcontractor_id') {
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
                // Resolve site name from common relationships if available
                $siteName = null;
                if (isset($model->shift) && isset($model->shift->site->site_name)) {
                    $siteName = $model->shift->site->site_name;
                } elseif (isset($model->site) && isset($model->site->site_name)) {
                    $siteName = $model->site->site_name;
                } elseif (isset($model->shift_id)) {
                    try {
                        $sd = \App\Models\ShiftDate::find($model->shift_id);
                        if ($sd && isset($sd->shift->site->site_name)) $siteName = $sd->shift->site->site_name;
                    } catch (\Throwable $_) {
                        // ignore lookup failures
                    }
                }
                $sitePart = $siteName ? (' at ' . $siteName) : '';
                $description .= " (Patrol: {$patrolName}{$patrolDue}{$sitePart})";
            }

            // If this is a CheckCall, include the checkcall name, scheduled time and site
            if ($modelType === 'CheckCall') {
                $ccName = $model->name ?? 'Unnamed CheckCall';
                $ccDue  = $model->scheduled_time ? (' due ' . $model->scheduled_time) : '';
                $siteName = null;
                if (isset($model->shift) && isset($model->shift->site->site_name)) {
                    $siteName = $model->shift->site->site_name;
                } elseif (isset($model->site) && isset($model->site->site_name)) {
                    $siteName = $model->site->site_name;
                } elseif (isset($model->shift_id)) {
                    try {
                        $sd = \App\Models\ShiftDate::find($model->shift_id);
                        if ($sd && isset($sd->shift->site->site_name)) $siteName = $sd->shift->site->site_name;
                    } catch (\Throwable $_) {
                        // ignore lookup failures
                    }
                }
                $sitePart = $siteName ? (' at ' . $siteName) : '';
                $description .= " (CheckCall: {$ccName}{$ccDue}{$sitePart})";
            }

            // Use central Logger helper so all logs follow the same format/resolution
            try {
                Logger::log($model, $actionTitle, $description);
            } catch (\Throwable $_) {
                // fallback: create directly if Logger fails
                try {
                    $model->logs()->create([
                        'admin_id'    => \App\Helpers\Logger::resolveAdminId($model),
                        'user_name'   => $username,
                        'action'      => $actionTitle,
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

            // If this is a Patrol, include the patrol name, due time and site for clearer context
            if ($modelType === 'Patrol') {
                $patrolName = $model->name ?? ($model->title ?? 'Unnamed Patrol');
                $patrolDue  = $model->start_time ? (' due ' . $model->start_time) : '';
                // Resolve site name if available
                $siteName = null;
                if (isset($model->shift) && isset($model->shift->site->site_name)) {
                    $siteName = $model->shift->site->site_name;
                } elseif (isset($model->site) && isset($model->site->site_name)) {
                    $siteName = $model->site->site_name;
                } elseif (isset($model->shift_id)) {
                    try {
                        $sd = \App\Models\ShiftDate::find($model->shift_id);
                        if ($sd && isset($sd->shift->site->site_name)) $siteName = $sd->shift->site->site_name;
                    } catch (\Throwable $_) {
                        // ignore
                    }
                }
                $sitePart = $siteName ? (' at ' . $siteName) : '';
                $description .= " (Patrol: {$patrolName}{$patrolDue}{$sitePart})";
            }

            // If this is a CheckCall, include the checkcall name, scheduled time and site
            if ($modelType === 'CheckCall') {
                $ccName = $model->name ?? 'Unnamed CheckCall';
                $ccDue  = $model->scheduled_time ? (' due ' . $model->scheduled_time) : '';
                $siteName = null;
                if (isset($model->shift) && isset($model->shift->site->site_name)) {
                    $siteName = $model->shift->site->site_name;
                } elseif (isset($model->site) && isset($model->site->site_name)) {
                    $siteName = $model->site->site_name;
                } elseif (isset($model->shift_id)) {
                    try {
                        $sd = \App\Models\ShiftDate::find($model->shift_id);
                        if ($sd && isset($sd->shift->site->site_name)) $siteName = $sd->shift->site->site_name;
                    } catch (\Throwable $_) {
                        // ignore
                    }
                }
                $sitePart = $siteName ? (' at ' . $siteName) : '';
                $description .= " (CheckCall: {$ccName}{$ccDue}{$sitePart})";
            }

            // Use central Logger helper so all logs follow the same format/resolution
            try {
                Logger::log($model, $actionTitle, $description);
            } catch (\Throwable $_) {
                try {
                    $model->logs()->create([
                        'admin_id'    => \App\Helpers\Logger::resolveAdminId($model),
                        'user_name'   => $username,
                        'action'      => $actionTitle,
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