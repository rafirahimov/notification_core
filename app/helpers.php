<?php


use Illuminate\Auth\Authenticatable;

if (!function_exists('current_user')) {
    function current_user(): \Illuminate\Contracts\Auth\Authenticatable
    {
        static $user = null;

        if ($user == null) {
            $user = auth()->user();
        }

        return $user;
    }
}