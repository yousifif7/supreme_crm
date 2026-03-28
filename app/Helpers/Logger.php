<?php

namespace App\Helpers;

use App\Models\Log;

class Logger
{
    /**
     * Resolve the admin_id that should be stamped on a log record.
     *
     * Priority order:
     *   1. Direct admin_id on the model (e.g. ShiftDate, Employee, Client)
     *   2. Via shiftDate relationship   (e.g. CheckCall, Patrol, ShiftBooking)
     *   3. Via shift relationship       (e.g. models that belong to a Shift)
     *   4. null – no admin ownership
     */
    public static function resolveAdminId($model): ?int
    {
        // 1. Direct
        if (!empty($model->admin_id)) {
            return (int) $model->admin_id;
        }

        // 2. Via shiftDate (CheckCall, Patrol, CheckpointScan …)
        try {
            if (method_exists($model, 'shiftDate') && $model->shift_id) {
                $sd = \App\Models\ShiftDate::withoutGlobalScope('admin_scope')
                    ->select('admin_id')
                    ->find($model->shift_id);
                if ($sd && $sd->admin_id) {
                    return (int) $sd->admin_id;
                }
            }
        } catch (\Throwable $_) {}

        // 3. Via shift (models with a shift() relation or a shift_id that maps to shifts)
        try {
            if (method_exists($model, 'shift') && !empty($model->shift_id)) {
                // First try interpreting shift_id as a ShiftDate id
                $sd = \App\Models\ShiftDate::withoutGlobalScope('admin_scope')
                    ->select('admin_id')
                    ->find($model->shift_id);
                if ($sd && $sd->admin_id) {
                    return (int) $sd->admin_id;
                }
            }
        } catch (\Throwable $_) {}

        return null;
    }

    public static function log($model, $action, $description = null)
    {
        // Accept an optional user context via the third parameter if callers pass it.
        // New signature supports: Logger::log($model, $action, $description = null, $user = null)
        $args = func_get_args();
        $userParam = $args[3] ?? null;

        try {
            $userObj = null;

             if ($userParam instanceof \App\Models\User) {
                $userObj = $userParam;
            } elseif (is_numeric($userParam)) {
                $userObj = \App\Models\User::find($userParam);
            } elseif (function_exists('request') && request() && request()->user()) {
                $userObj = request()->user();
            } else {
                $userObj = null; // prefer System when no request user is available
            }

            if ($userObj) {
                $first = $userObj->first_name ?? $userObj->fore_name ?? '';
                $last = $userObj->last_name ?? $userObj->sur_name ?? '';
                $userName = trim($first . ' ' . $last);
            } else {
                $userName = 'System';
            }

            // Guard: ensure model has logs relation (avoid fatal in unexpected contexts)
            if (method_exists($model, 'logs')) {
                $model->logs()->create([
                    'admin_id'    => self::resolveAdminId($model),
                    'user_name'   => $userName ?: 'System',
                    'action'      => $action,
                    'description' => $description,
                ]);
            }
        } catch (\Throwable $e) {
            // Swallow errors to avoid interfering with main flow; log to default logger
            try {
                \Log::warning('Logger::log failed: ' . $e->getMessage(), ['action' => $action]);
            } catch (\Throwable $_) {
                // nothing
            }
        }
    }
}
