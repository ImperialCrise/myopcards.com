<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use PDO;

class SeoController
{
    public function sitemap(): void
    {
        header('Content-Type: application/xml; charset=UTF-8');
        $db = Database::getConnection();
        $base = 'https://myopcards.com';
        $now = date('Y-m-d');

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        $static = [
            ['/', '1.0', 'daily'],
            ['/cards', '0.9', 'daily'],
            ['/market', '0.8', 'daily'],
            ['/login', '0.3', 'monthly'],
            ['/register', '0.3', 'monthly'],
        ];

        foreach ($static as [$path, $priority, $freq]) {
            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars($base . $path) . "</loc>\n";
            echo "    <lastmod>{$now}</lastmod>\n";
            echo "    <changefreq>{$freq}</changefreq>\n";
            echo "    <priority>{$priority}</priority>\n";
            echo "  </url>\n";
        }

        $cards = $db->query(
            "SELECT card_set_id, COALESCE(price_updated_at, NOW()) as updated
             FROM cards ORDER BY set_id ASC, card_set_id ASC"
        )->fetchAll();

        foreach ($cards as $card) {
            $loc = $base . '/cards/' . rawurlencode($card['card_set_id']);
            $mod = date('Y-m-d', strtotime($card['updated']));
            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars($loc) . "</loc>\n";
            echo "    <lastmod>{$mod}</lastmod>\n";
            echo "    <changefreq>weekly</changefreq>\n";
            echo "    <priority>0.7</priority>\n";
            echo "  </url>\n";
        }

        $users = $db->query(
            "SELECT username, updated_at FROM users WHERE is_public = 1 ORDER BY id ASC"
        )->fetchAll();

        foreach ($users as $u) {
            $loc = $base . '/user/' . rawurlencode($u['username']);
            $mod = date('Y-m-d', strtotime($u['updated_at']));
            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars($loc) . "</loc>\n";
            echo "    <lastmod>{$mod}</lastmod>\n";
            echo "    <changefreq>weekly</changefreq>\n";
            echo "    <priority>0.5</priority>\n";
            echo "  </url>\n";

            $colLoc = $base . '/collection/' . rawurlencode($u['username']);
            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars($colLoc) . "</loc>\n";
            echo "    <lastmod>{$mod}</lastmod>\n";
            echo "    <changefreq>weekly</changefreq>\n";
            echo "    <priority>0.4</priority>\n";
            echo "  </url>\n";
        }

        echo '</urlset>';
        exit;
    }

    public function robots(): void
    {
        header('Content-Type: text/plain');
        echo "User-agent: *\n";
        echo "Allow: /\n";
        echo "Disallow: /api/\n";
        echo "Disallow: /auth/\n";
        echo "Disallow: /login\n";
        echo "Disallow: /register\n";
        echo "Disallow: /settings\n";
        echo "Disallow: /profile\n";
        echo "Disallow: /dashboard\n";
        echo "Disallow: /analytics\n";
        echo "Disallow: /friends\n";
        echo "Disallow: /collection/add\n";
        echo "Disallow: /collection/remove\n";
        echo "Disallow: /collection/update\n";
        echo "Disallow: /collection/export\n";
        echo "Disallow: /collection/share\n\n";
        echo "Sitemap: https://myopcards.com/sitemap.xml\n";
        exit;
    }
}
