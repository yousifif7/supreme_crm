<?php

namespace App\Traits;

use App\Models\Log;
use Illuminate\Support\Facades\Auth;

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
            $labels = 'Updated <br>';

            foreach ($dirty as $field => $newValue) {
                $oldValue = $model->getOriginal($field);

                if ($field == 'staff_id') {
                    $oldStaff = \App\Models\User::find($oldValue);
                    $oldValue = isset($oldStaff->first_name) ? $oldStaff->first_name . ' ' . $oldStaff->last_name : null;
                    $newValue = isset($model->staff->first_name) ? $model->staff?->first_name . ' ' . $model->staff?->last_name : 'N/A';
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

                if (isset($arrayValues[$field])) {
                    $field = $arrayValues[$field];
                }

                $label = "$field";

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

                $fields .= $field . ', ';
                $labels .= $label . ',<br> ';
            }

            $fields = rtrim($fields, ', ');
            $labels = rtrim($labels, ',<br> ');

            $model->logs()->create([
                'user_name' => optional(Auth::user())->first_name ?? 'System' . ' ' . optional(Auth::user())->last_name ?? 'System',
                'action' => "Updated {$fields}",
                'description' => $labels,
            ]);
        });

        static::created(function ($model) {
            $modelType = class_basename($model); // e.g., "Client" or "Site"

            // Try to get a name field, or fallback to ID
            $label = $model->client_name ?? $model->site_name ?? $model->fore_name ?? $model->shift->fore_name ?? $model->company_name ?? $model->first_name ?? $model->name ?? $model->id;

            // create shift logs description
            if ($modelType == 'ShiftDate') {
                $modelType = 'Shift';
                $staff = isset($model->staff->first_name) ? $model->staff?->first_name . ' ' . $model->staff?->last_name : 'N/A';
                $site = $model->shift->site->site_name ?? 'N/A';
                $date = $model->shift_date ?? 'N/A';
                $start = $model->start_time ?? 'N/A';
                $end = $model->end_time ?? 'N/A';

                // $label = "for the Staff ($staff) on site ($site) on $date from $start to $end";
                $label = '';
            }

            if (auth::user()) {
                $username = Auth::user()->first_name . ' ' . Auth::user()->last_name;
            } else {
                $username = 'System';
            }

            $model->logs()->create([
                'user_name' => $username,
                'action' => "Created {$modelType} record",
                'description' => "{$modelType} {$label} was added successfully.",
            ]);
        });
    }

    public function logs()
    {
        return $this->morphMany(Log::class, 'loggable')->orderBy('created_at', 'desc');
    }
}
