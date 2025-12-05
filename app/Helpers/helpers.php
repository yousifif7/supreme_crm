<?php

use Carbon\Carbon;
use App\Models\Docs\AdminSetting;

if (!function_exists('format_date')) {
    /**
     * Format a date string or Carbon instance as m/d/Y.
     * Returns empty string on null/invalid input.
     *
     * @param  mixed  $date
     * @return string
     */
    function format_date($date)
    {
        if (is_null($date) || $date === '') return '';

        try {
            if ($date instanceof Carbon) {
                return $date->format('d/m/Y');
            }
            return Carbon::parse($date)->format('d/m/Y');
        } catch (\Exception $e) {
            return '';
        }
    }
}


if (!function_exists('get_setting')) {
    function get_setting($key, $default = null, $lang = false)
    {
 
        $settings = Cache::remember('AdminSetting', 2, function () {
            return AdminSetting::all();
        });

        if ($lang == false) {
            $setting = $settings->where('type', $key)->first();
            
        } else {
            $setting = $settings->where('type', $key)->where('lang', $lang)->first();
            $setting = !$setting ? $settings->where('type', $key)->first() : $setting;
        }
        return $setting == null ? $default : $setting->value;
    }
}