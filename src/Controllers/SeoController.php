<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use PDO;

class SeoController
{
    private const BASE_URL = 'https://myopcards.com';

    public function sitemapIndex(): void
    {
        header('Content-Type: application/xml; charset=UTF-8');
        $now = date('Y-m-d\TH:i:s+00:00');

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        $sitemaps = [
            'sitemap-static.xml',
            'sitemap-cards-premium.xml',
            'sitemap-cards.xml',
            'sitemap-users.xml',
            'sitemap-forum.xml',
        ];

        foreach ($sitemaps as $file) {
            echo "  <sitemap>\n";
            echo "    <loc>" . self::BASE_URL . "/{$file}</loc>\n";
            echo "    <lastmod>{$now}</lastmod>\n";
            echo "  </sitemap>\n";
        }

        echo '</sitemapindex>';
        exit;
    }

    public function sitemapStatic(): void
    {
        header('Content-Type: application/xml; charset=UTF-8');
        $now = date('Y-m-d');

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        $static = [
            ['/', '1.0', 'daily'],
            ['/cards', '0.9', 'daily'],
            ['/market', '0.9', 'daily'],
        ];

        foreach ($static as [$path, $priority, $freq]) {
            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars(self::BASE_URL . $path) . "</loc>\n";
            echo "    <lastmod>{$now}</lastmod>\n";
            echo "    <changefreq>{$freq}</changefreq>\n";
            echo "    <priority>{$priority}</priority>\n";
            echo "  </url>\n";
        }

        echo '</urlset>';
        exit;
    }

    public function sitemapCardsPremium(): void
    {
        header('Content-Type: application/xml; charset=UTF-8');
        $db = Database::getConnection();

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        $cards = $db->query(
            "SELECT card_set_id, rarity, is_parallel, COALESCE(market_price, 0) as price,
                    COALESCE(price_updated_at, NOW()) as updated
             FROM cards 
             WHERE rarity IN ('SEC', 'SP', 'L') 
                OR COALESCE(market_price, 0) >= 15
             ORDER BY 
                CASE rarity 
                    WHEN 'SEC' THEN 1 
                    WHEN 'SP' THEN 2 
                    WHEN 'L' THEN 3 
                    ELSE 4 
                END,
                market_price DESC,
                card_set_id ASC"
        )->fetchAll();

        foreach ($cards as $card) {
            $priority = $this->calculatePriority($card['rarity'], (float)$card['price'], (bool)$card['is_parallel']);
            $loc = self::BASE_URL . '/cards/' . rawurlencode($card['card_set_id']);
            $mod = date('Y-m-d', strtotime($card['updated']));

            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars($loc) . "</loc>\n";
            echo "    <lastmod>{$mod}</lastmod>\n";
            echo "    <changefreq>daily</changefreq>\n";
            echo "    <priority>{$priority}</priority>\n";
            echo "  </url>\n";
        }

        echo '</urlset>';
        exit;
    }

    public function sitemapCards(): void
    {
        header('Content-Type: application/xml; charset=UTF-8');
        $db = Database::getConnection();

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        $cards = $db->query(
            "SELECT card_set_id, rarity, is_parallel, COALESCE(market_price, 0) as price,
                    COALESCE(price_updated_at, NOW()) as updated
             FROM cards 
             WHERE rarity NOT IN ('SEC', 'SP', 'L') 
                AND COALESCE(market_price, 0) < 15
             ORDER BY 
                CASE rarity 
                    WHEN 'SR' THEN 1 
                    WHEN 'R' THEN 2 
                    WHEN 'UC' THEN 3 
                    WHEN 'C' THEN 4 
                    ELSE 5 
                END,
                card_set_id ASC"
        )->fetchAll();

        foreach ($cards as $card) {
            $priority = $this->calculatePriority($card['rarity'], (float)$card['price'], (bool)$card['is_parallel']);
            $loc = self::BASE_URL . '/cards/' . rawurlencode($card['card_set_id']);
            $mod = date('Y-m-d', strtotime($card['updated']));

            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars($loc) . "</loc>\n";
            echo "    <lastmod>{$mod}</lastmod>\n";
            echo "    <changefreq>weekly</changefreq>\n";
            echo "    <priority>{$priority}</priority>\n";
            echo "  </url>\n";
        }

        echo '</urlset>';
        exit;
    }

    public function sitemapUsers(): void
    {
        header('Content-Type: application/xml; charset=UTF-8');
        $db = Database::getConnection();

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        $users = $db->query(
            "SELECT username, updated_at FROM users WHERE is_public = 1 ORDER BY id ASC"
        )->fetchAll();

        foreach ($users as $u) {
            $mod = date('Y-m-d', strtotime($u['updated_at']));

            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars(self::BASE_URL . '/user/' . rawurlencode($u['username'])) . "</loc>\n";
            echo "    <lastmod>{$mod}</lastmod>\n";
            echo "    <changefreq>weekly</changefreq>\n";
            echo "    <priority>0.6</priority>\n";
            echo "  </url>\n";

            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars(self::BASE_URL . '/collection/' . rawurlencode($u['username'])) . "</loc>\n";
            echo "    <lastmod>{$mod}</lastmod>\n";
            echo "    <changefreq>weekly</changefreq>\n";
            echo "    <priority>0.5</priority>\n";
            echo "  </url>\n";
        }

        echo '</urlset>';
        exit;
    }

    public function sitemapForum(): void
    {
        header('Content-Type: application/xml; charset=UTF-8');
        $db = Database::getConnection();

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        echo "  <url>\n";
        echo "    <loc>" . self::BASE_URL . "/forum</loc>\n";
        echo "    <changefreq>daily</changefreq>\n";
        echo "    <priority>0.8</priority>\n";
        echo "  </url>\n";

        $categories = $db->query("SELECT slug FROM forum_categories ORDER BY sort_order")->fetchAll();
        foreach ($categories as $cat) {
            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars(self::BASE_URL . '/forum/' . $cat['slug']) . "</loc>\n";
            echo "    <changefreq>daily</changefreq>\n";
            echo "    <priority>0.7</priority>\n";
            echo "  </url>\n";
        }

        $topics = $db->query(
            "SELECT t.id, t.slug, c.slug as category_slug, 
                    COALESCE(t.last_reply_at, t.created_at) as updated
             FROM forum_topics t
             JOIN forum_categories c ON c.id = t.category_id
             ORDER BY t.created_at DESC
             LIMIT 1000"
        )->fetchAll();

        foreach ($topics as $t) {
            $loc = self::BASE_URL . '/forum/' . $t['category_slug'] . '/' . $t['id'] . '-' . $t['slug'];
            $mod = date('Y-m-d', strtotime($t['updated']));
            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars($loc) . "</loc>\n";
            echo "    <lastmod>{$mod}</lastmod>\n";
            echo "    <changefreq>weekly</changefreq>\n";
            echo "    <priority>0.6</priority>\n";
            echo "  </url>\n";
        }

        echo '</urlset>';
        exit;
    }

    private function calculatePriority(string $rarity, float $price, bool $isParallel): string
    {
        $base = match ($rarity) {
            'SEC' => 0.95,
            'SP'  => 0.90,
            'L'   => 0.85,
            'SR'  => 0.70,
            'R'   => 0.55,
            'UC'  => 0.45,
            'C'   => 0.40,
            default => 0.50,
        };

        if ($price >= 100) {
            $base = max($base, 0.95);
        } elseif ($price >= 50) {
            $base = max($base, 0.90);
        } elseif ($price >= 25) {
            $base = max($base, 0.80);
        } elseif ($price >= 15) {
            $base = max($base, 0.70);
        }

        if ($isParallel && $base < 0.85) {
            $base += 0.10;
        }

        return number_format(min($base, 0.99), 2);
    }

    public function robots(): void
    {
        header('Content-Type: text/plain');
        echo "User-agent: *\n";
        echo "Allow: /\n";
        echo "Allow: /cards\n";
        echo "Allow: /cards/\n";
        echo "Allow: /market\n";
        echo "Allow: /user/\n";
        echo "Allow: /collection/\n";
        echo "Allow: /forum\n";
        echo "Allow: /forum/\n\n";
        echo "Disallow: /api/\n";
        echo "Disallow: /auth/\n";
        echo "Disallow: /login\n";
        echo "Disallow: /register\n";
        echo "Disallow: /settings\n";
        echo "Disallow: /profile\n";
        echo "Disallow: /dashboard\n";
        echo "Disallow: /analytics\n";
        echo "Disallow: /friends\n";
        echo "Disallow: /admin\n";
        echo "Disallow: /s/\n";
        echo "Disallow: /collection/add\n";
        echo "Disallow: /collection/remove\n";
        echo "Disallow: /collection/update\n";
        echo "Disallow: /collection/export\n";
        echo "Disallow: /collection/share\n\n";
        echo "Sitemap: https://myopcards.com/sitemap.xml\n";
        exit;
    }
}
