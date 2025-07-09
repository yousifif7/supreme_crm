<?php

namespace App\Traits;

use App\Models\Log;
use Illuminate\Support\Facades\Auth;

trait LogsChanges
{
    public static function bootLogsChanges()
    {
        static::updating(function ($model) {
            $dirty = $model->getDirty();
            foreach ($dirty as $field => $newValue) {
                $oldValue = $model->getOriginal($field);
                $model->logs()->create([
                    'user_name' => Auth::user()->name ?? 'System',
                    'action' => "Updated {$field}",
                    'description' => "Changed {$field} from '{$oldValue}' to '{$newValue}'",
                ]);
            }
        });

        static::created(function ($model) {
            $modelType = class_basename($model); // e.g., "Client" or "Site"

            // Try to get a name field, or fallback to ID
            $label = $model->client_name ?? $model->site_name ?? $model->fore_name ?? $model->shift->fore_name ?? $model->company_name ?? $model->first_name ?? $model->name ?? $model->id;

            $model->logs()->create([
                'user_name' => Auth::user()->first_name ?? 'System',
                'action' => "Created {$modelType} record",
                'description' => "{$modelType} '{$label}' was added.",
            ]);
        });
    }

    public function logs()
    {
        return $this->morphMany(Log::class, 'loggable')->orderBy('created_at', 'desc');
    }
}
