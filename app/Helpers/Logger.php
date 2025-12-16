<?php

namespace App\Helpers;

use App\Models\Log;

class Logger
{
    public static function log($model, $action, $description = null)
    {
        $user = auth()->user();
        $userName = 'System';


        $model->logs()->create([
            'user_name'   => $user->email ?? 'System',
            'action'      => $action,
            'description' => $description,
        ]);
    }
}
