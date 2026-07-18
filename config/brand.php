<?php

return [

    /*
    |--------------------------------------------------------------------------
    | White-label product brand
    |--------------------------------------------------------------------------
    | Change these (or override via .env) when shipping a client install.
    */

    'name' => env('BRAND_NAME', 'FieldLine'),

    /*
    | FieldLine = product brand for this CRM (not an acronym).
    | Evokes field officers + the operational "line" that connects sites, guards, and control.
    | Override via .env BRAND_* or General Settings → Brand name.
    */

    'tagline' => env('BRAND_TAGLINE', 'Site operations for security teams'),

    'company' => env('BRAND_COMPANY', 'FieldLine'),

    'primary' => env('BRAND_PRIMARY', '#0B1F3A'),

    'accent' => env('BRAND_ACCENT', '#1FA7A0'),

    'surface' => env('BRAND_SURFACE', '#F4F6F8'),

    /** Fallback mark when admin settings logos are empty */
    'mark' => env('BRAND_MARK', 'assets/fieldline-mark.svg'),

    'favicon' => env('BRAND_FAVICON', 'assets/fieldline-mark.svg'),

    'email' => env('BRAND_EMAIL', 'support@fieldline.app'),

    'address' => env('BRAND_ADDRESS', 'Your company address'),

    'registration_no' => env('BRAND_REGISTRATION_NO', ''),

];
