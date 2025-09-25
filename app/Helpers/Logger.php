<?php

namespace App\Helpers;

use App\Models\Log;

class Logger
{
    public static function log($model, $action, $description = null)
    {
        $model->logs()->create([
            'user_name'   => auth()->user()->first_name .' '.auth()->user()->last_name ?? 'System',
            'action'      => $action,
            'description' => $description,
        ]);
    }
}
