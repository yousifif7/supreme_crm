<?php

namespace App\Traits;

use App\Models\Log;
use App\Helpers\Logger;

trait LogsChanges
{
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
                    $oldStaff = \App\Models\User::find($oldValue);
                    $newStaff = \App\Models\User::find($newValue);
                    
                    $oldStaffName = $oldStaff ? ($oldStaff->first_name . ' ' . $oldStaff->last_name) : null;
                    $newStaffName = $newStaff ? ($newStaff->first_name . ' ' . $newStaff->last_name) : null;
                    
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

            // Use centralized Logger helper so it can prefer request()->user() and accept an explicit user
            try {
                Logger::log($model, $actionTitle, $description);
            } catch (\Throwable $e) {
                try { \Log::warning('LogsChanges::updating logger failed: ' . $e->getMessage()); } catch (\Throwable $_) {}
            }
        });

        static::created(function ($model) {
            $modelType = class_basename($model); // e.g., "Client" or "Site"

            // Try to get a name field, or fallback to ID
            $label = $model->client_name ?? $model->site_name ?? $model->fore_name ?? $model->shift->fore_name ?? $model->company_name ?? $model->first_name ?? $model->name ?? $model->id;

            // create shift logs description
            if ($modelType == 'ShiftDate') {
                $modelType = 'Shift';
                $staff = isset($model->staff->first_name) ? $model->staff->first_name . ' ' . $model->staff->last_name : 'Unassigned';
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

            $actionTitle = "Created {$modelType}";
            // Do not include the username inside the description (we store it in the `user_name` column)
            $description = "{$actionTitle} {$label}";

            try {
                Logger::log($model, $actionTitle, $description);
            } catch (\Throwable $e) {
                try { \Log::warning('LogsChanges::created logger failed: ' . $e->getMessage()); } catch (\Throwable $_) {}
            }
        });
    }

    public function logs()
    {
        return $this->morphMany(Log::class, 'loggable')->orderBy('created_at', 'desc');
    }
}
