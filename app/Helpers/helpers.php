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

if (!function_exists('brand_name')) {
    function brand_name(): string
    {
        $fromSettings = function_exists('get_setting') ? get_setting('website_name') : null;
        if (!empty($fromSettings)) {
            return (string) $fromSettings;
        }

        return (string) config('brand.name', config('app.name', 'FieldLine'));
    }
}

if (!function_exists('brand_tagline')) {
    function brand_tagline(): string
    {
        return (string) config('brand.tagline', 'Site operations for security teams');
    }
}

if (!function_exists('brand_company')) {
    function brand_company(): string
    {
        return (string) config('brand.company', brand_name());
    }
}

if (!function_exists('brand_title')) {
    /**
     * Browser / page title: "FieldLine" or "FieldLine - Section"
     */
    function brand_title(?string $section = null): string
    {
        $name = brand_name();
        if ($section === null || trim($section) === '') {
            return $name;
        }

        return $name . ' - ' . $section;
    }
}

if (!function_exists('brand_logo_url')) {
    /**
     * Prefer admin setting logo, then brand mark asset.
     *
     * @param  string  $settingKey  dashboard_logo | login_logo | favicon_logo
     */
    function brand_logo_url(string $settingKey = 'dashboard_logo'): string
    {
        $fromSettings = get_setting($settingKey);
        if (!empty($fromSettings) && is_file(public_path('backend/websitedata/' . $fromSettings))) {
            return asset('backend/websitedata/' . $fromSettings);
        }

        return asset(config('brand.mark', 'assets/fieldline-mark.svg'));
    }
}

if (!function_exists('brand_email')) {
    function brand_email(): string
    {
        return (string) config('brand.email', 'support@fieldline.app');
    }
}

if (!function_exists('brand_address')) {
    function brand_address(): string
    {
        return (string) config('brand.address', 'Your company address');
    }
}

if (!function_exists('brand_favicon_url')) {
    function brand_favicon_url(): string
    {
        foreach (['favicon_logo', 'dashboard_logo'] as $key) {
            $fromSettings = get_setting($key);
            if (!empty($fromSettings) && is_file(public_path('backend/websitedata/' . $fromSettings))) {
                return asset('backend/websitedata/' . $fromSettings);
            }
        }

        return asset(config('brand.favicon', 'assets/fieldline-mark.svg'));
    }
}
