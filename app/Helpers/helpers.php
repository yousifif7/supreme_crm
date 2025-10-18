<?php

use Carbon\Carbon;

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
                return $date->format('m/d/Y');
            }
            return Carbon::parse($date)->format('m/d/Y');
        } catch (\Exception $e) {
            return '';
        }
    }
}
