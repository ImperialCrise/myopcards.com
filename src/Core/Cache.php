<?php

declare(strict_types=1);

namespace App\Core;

class Cache
{
    private static string $cacheDir = BASE_PATH . '/storage/cache';

    public static function init(): void
    {
        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0755, true);
        }
    }

    public static function get(string $key, $default = null)
    {
        $file = self::$cacheDir . '/' . self::sanitizeKey($key) . '.cache';
        
        if (!file_exists($file)) {
            return $default;
        }

        $data = file_get_contents($file);
        if ($data === false) {
            return $default;
        }

        $decoded = unserialize($data);
        if (!is_array($decoded) || !isset($decoded['expires'], $decoded['data'])) {
            return $default;
        }

        if ($decoded['expires'] < time()) {
            unlink($file);
            return $default;
        }

        return $decoded['data'];
    }

    public static function set(string $key, $value, int $seconds = 3600): bool
    {
        self::init();
        
        $file = self::$cacheDir . '/' . self::sanitizeKey($key) . '.cache';
        $data = [
            'expires' => time() + $seconds,
            'data' => $value
        ];

        return file_put_contents($file, serialize($data)) !== false;
    }

    public static function remember(string $key, callable $callback, int $seconds = 3600)
    {
        $value = self::get($key);
        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        self::set($key, $value, $seconds);
        return $value;
    }

    public static function forget(string $key): bool
    {
        $file = self::$cacheDir . '/' . self::sanitizeKey($key) . '.cache';
        if (file_exists($file)) {
            return unlink($file);
        }
        return true;
    }

    public static function flush(): bool
    {
        $files = glob(self::$cacheDir . '/*.cache');
        if ($files === false) return true;
        
        foreach ($files as $file) {
            unlink($file);
        }
        return true;
    }

    public static function forgetPattern(string $pattern): int
    {
        $files = glob(self::$cacheDir . '/' . self::sanitizeKey($pattern) . '*.cache');
        if ($files === false) return 0;
        
        $count = 0;
        foreach ($files as $file) {
            if (unlink($file)) {
                $count++;
            }
        }
        return $count;
    }

    private static function sanitizeKey(string $key): string
    {
        return preg_replace('/[^a-zA-Z0-9._-]/', '_', $key);
    }

    public static function cleanExpired(): int
    {
        $files = glob(self::$cacheDir . '/*.cache');
        if ($files === false) return 0;
        
        $count = 0;
        foreach ($files as $file) {
            $data = file_get_contents($file);
            if ($data !== false) {
                $decoded = unserialize($data);
                if (is_array($decoded) && isset($decoded['expires']) && $decoded['expires'] < time()) {
                    if (unlink($file)) {
                        $count++;
                    }
                }
            }
        }
        return $count;
    }
}