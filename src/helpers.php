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
