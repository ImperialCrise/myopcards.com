<?php

declare(strict_types=1);

if (!function_exists('t')) {
    function t(string $key, array $replace = []): string
    {
        return \App\Services\Lang::get($key, $replace);
    }
}
