<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Gantt API Response Cache TTL
    |--------------------------------------------------------------------------
    |
    | How long (in seconds) to cache the /api/shifts response per unique
    | request-filter combination.  Raising this reduces DB load significantly
    | for teams that refresh the scheduling board frequently.
    |
    | Default: 120 seconds (2 minutes).
    | Set to 0 to disable caching entirely (not recommended for production).
    |
    */
    'cache_ttl' => env('GANTT_CACHE_TTL', 30),
];
