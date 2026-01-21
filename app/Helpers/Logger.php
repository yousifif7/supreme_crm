<?php

namespace App\Helpers;

use App\Models\Log;

class Logger
{
    public static function log($model, $action, $description = null)
    {
        $user = auth()->user();
        $userName = $user->first_name.' '.$user->last_name;


        $model->logs()->create([
            'user_name'   => $userName ?? 'System',
            'action'      => $action,
            'description' => $description,
        ]);
    }
}
