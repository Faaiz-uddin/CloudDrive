<?php
use App\Models\Setting;

if (!function_exists('app_setting')) {
    function app_setting($key)
    {
        return optional(Setting::first())->{$key};
    }
}
