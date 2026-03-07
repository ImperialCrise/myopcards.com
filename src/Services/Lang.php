<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;

class Lang
{
    private static ?string $locale = null;

    private static ?array $translations = null;

    private static array $fallback = [];

    public static function getLocale(): string
    {
        if (self::$locale !== null) {
            return self::$locale;
        }
        $locale = 'en';
        if (!empty($_COOKIE['lang']) && self::isAllowed($_COOKIE['lang'])) {
            $locale = $_COOKIE['lang'];
        } elseif (Auth::check()) {
            $user = Auth::user();
            $pref = $user['preferred_lang'] ?? '';
            if (self::isAllowed($pref)) {
                $locale = $pref;
            }
        }
        self::$locale = $locale;
        return $locale;
    }

    public static function setLocale(string $locale): void
    {
        if (self::isAllowed($locale)) {
            self::$locale = $locale;
            self::$translations = null;
        }
    }

    public static function isAllowed(string $locale): bool
    {
        return in_array($locale, ['en', 'fr', 'ja', 'ko', 'th', 'zh'], true);
    }

    public static function load(): void
    {
        if (self::$translations !== null) {
            return;
        }
        $locale = self::getLocale();
        $path = BASE_PATH . '/src/lang/' . $locale . '.php';
        if (!is_file($path)) {
            $path = BASE_PATH . '/src/lang/en.php';
        }
        self::$translations = require $path;
        if ($locale !== 'en') {
            $enPath = BASE_PATH . '/src/lang/en.php';
            self::$fallback = is_file($enPath) ? (require $enPath) : [];
        }
    }

    public static function get(string $key, array $replace = []): string
    {
        self::load();
        $value = self::$translations[$key] ?? self::$fallback[$key] ?? $key;
        foreach ($replace as $k => $v) {
            $value = str_replace((string) $k, (string) $v, $value);
        }
        return $value;
    }

    public static function getAll(): array
    {
        self::load();
        return self::$translations ?? [];
    }
}
