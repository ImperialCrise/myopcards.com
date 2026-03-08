<?php

declare(strict_types=1);

if (!function_exists('t')) {
    function t(string $key, array $replace = []): string
    {
        return \App\Services\Lang::get($key, $replace);
    }
}

if (!function_exists('avatar_url')) {
    function avatar_url(array $user): string
    {
        return \App\Models\User::getAvatarUrl($user);
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return \App\Core\Auth::csrfToken();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}
