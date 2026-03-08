<?php

declare(strict_types=1);

namespace App\Core;

class RateLimiter
{
    private const PREFIX = 'ratelimit_';
    private const WINDOW = 900;
    private const MAX_ATTEMPTS = 5;

    public static function isLimited(string $key, int $maxAttempts = self::MAX_ATTEMPTS, int $windowSeconds = self::WINDOW): bool
    {
        $fullKey = self::PREFIX . $key;
        $data = Cache::get($fullKey);
        if ($data === null) {
            return false;
        }
        [$count, $windowStart] = is_array($data) ? $data : [0, 0];
        if (time() - $windowStart > $windowSeconds) {
            return false;
        }
        return $count >= $maxAttempts;
    }

    public static function recordAttempt(string $key, int $windowSeconds = self::WINDOW): void
    {
        $fullKey = self::PREFIX . $key;
        $data = Cache::get($fullKey);
        $now = time();
        if ($data === null || !is_array($data)) {
            Cache::set($fullKey, [1, $now], $windowSeconds);
            return;
        }
        [$count, $windowStart] = $data;
        if ($now - $windowStart > $windowSeconds) {
            Cache::set($fullKey, [1, $now], $windowSeconds);
            return;
        }
        Cache::set($fullKey, [$count + 1, $windowStart], $windowSeconds - ($now - $windowStart));
    }

    public static function getRemainingSeconds(string $key, int $windowSeconds = self::WINDOW): int
    {
        $data = Cache::get(self::PREFIX . $key);
        if ($data === null || !is_array($data)) {
            return 0;
        }
        [, $windowStart] = $data;
        $elapsed = time() - $windowStart;
        return max(0, $windowSeconds - $elapsed);
    }
}
