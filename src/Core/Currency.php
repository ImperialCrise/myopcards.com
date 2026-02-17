<?php

declare(strict_types=1);

namespace App\Core;

class Currency
{
    private static ?string $current = null;

    private static array $map = [
        'usd'    => ['symbol' => '$', 'label' => 'USD', 'column' => 'market_price', 'source' => 'tcgplayer', 'edition' => 'en'],
        'eur_en' => ['symbol' => '€', 'label' => 'EUR', 'column' => 'price_en',     'source' => 'cardmarket', 'edition' => 'en'],
        'eur_fr' => ['symbol' => '€', 'label' => 'EUR', 'column' => 'price_fr',     'source' => 'cardmarket', 'edition' => 'fr'],
        'eur_jp' => ['symbol' => '€', 'label' => 'EUR', 'column' => 'price_jp',     'source' => 'cardmarket', 'edition' => 'jp'],
    ];

    public static function current(): string
    {
        if (self::$current !== null) return self::$current;

        if (Auth::check()) {
            $user = Auth::user();
            self::$current = $user['preferred_currency'] ?? 'usd';
        } elseif (isset($_COOKIE['currency'])) {
            self::$current = $_COOKIE['currency'];
        } else {
            self::$current = 'usd';
        }

        if (!isset(self::$map[self::$current])) self::$current = 'usd';
        return self::$current;
    }

    public static function symbol(): string
    {
        return self::$map[self::current()]['symbol'];
    }

    public static function label(): string
    {
        return self::$map[self::current()]['label'];
    }

    public static function column(): string
    {
        return self::$map[self::current()]['column'];
    }

    public static function source(): string
    {
        return self::$map[self::current()]['source'];
    }

    public static function edition(): string
    {
        return self::$map[self::current()]['edition'];
    }

    public static function format(float $price): string
    {
        return self::symbol() . number_format($price, 2);
    }

    public static function priceFromCard(array $card): float
    {
        $col = self::column();
        return (float)($card[$col] ?? 0);
    }

    public static function info(): array
    {
        $cur = self::current();
        return [
            'key' => $cur,
            'symbol' => self::symbol(),
            'label' => self::label(),
            'column' => self::column(),
            'source' => self::source(),
            'edition' => self::edition(),
        ];
    }
}
