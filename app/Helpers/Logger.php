<?php

namespace App\Helpers;

use App\Models\Log;

class Logger
{
    public static function log($model, $action, $description = null)
    {
        // Accept an optional user context via the third parameter if callers pass it.
        // New signature supports: Logger::log($model, $action, $description = null, $user = null)
        $args = func_get_args();
        $userParam = $args[3] ?? null;

        try {
            $userObj = null;

            // Prefer explicit user passed in, then request()->user(), then auth()->user()
            if ($userParam instanceof \App\Models\User) {
                $userObj = $userParam;
            } elseif (is_numeric($userParam)) {
                $userObj = \App\Models\User::find($userParam);
            } elseif (function_exists('request') && request() && request()->user()) {
                $userObj = request()->user();
            } else {
                $userObj = auth()->user();
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
