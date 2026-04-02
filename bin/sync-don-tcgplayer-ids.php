<?php

declare(strict_types=1);

/**
 * Build config/don_tcgplayer_product_ids.json by matching OPTCG DON!! cards to TCGPlayer product IDs.
 *
 * Modes:
 *   --search   POST mp-search-api (same filters as the site search UI; no browser / FlareSolverr).
 *              See: https://www.tcgplayer.com/search/one-piece-card-game/product?q=Don!!&view=grid&productLineName=one-piece-card-game&setName=product
 *   --scan     Brute-force infinite-api.tcgplayer.com/product/{id} between --from and --to (slow).
 *
 * Usage:
 *   php bin/sync-don-tcgplayer-ids.php --search [--page-size=50] [--sleep-ms=12] [--dry-run]
 *   php bin/sync-don-tcgplayer-ids.php --scan --from=618000 --to=720000 [--sleep-ms=12] [--dry-run]
 *
 * Optional HTTP/SOCKS proxy: SCRAPING_HTTP_PROXY, TCGPLAYER_HTTP_PROXY, CARDMARKET_FLARE_CURL_PROXY, HTTP_PROXY.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

$opts = getopt('', ['search', 'scan', 'from:', 'to:', 'page-size:', 'sleep-ms:', 'dry-run', 'help']);

if (isset($opts['help']) || (!isset($opts['search']) && !isset($opts['scan']))) {
    echo <<<TXT
sync-don-tcgplayer-ids.php — fill config/don_tcgplayer_product_ids.json

  php bin/sync-don-tcgplayer-ids.php --search [--page-size=50] [--sleep-ms=12] [--dry-run]
  php bin/sync-don-tcgplayer-ids.php --scan --from=618000 --to=720000 [--sleep-ms=12] [--dry-run]

Environment: SCRAPING_HTTP_PROXY / TCGPLAYER_HTTP_PROXY / CARDMARKET_FLARE_CURL_PROXY / HTTP_PROXY

TXT;
    exit(isset($opts['help']) ? 0 : 1);
}

if (isset($opts['search']) && isset($opts['scan'])) {
    fwrite(STDERR, "Use either --search or --scan, not both.\n");
    exit(1);
}

$dryRun = isset($opts['dry-run']);
$sleepUs = max(0, (int)($opts['sleep-ms'] ?? 12)) * 1000;

function scrapingProxy(): ?string
{
    foreach (
        [
            $_ENV['SCRAPING_HTTP_PROXY'] ?? '',
            $_ENV['TCGPLAYER_HTTP_PROXY'] ?? '',
            $_ENV['CARDMARKET_FLARE_CURL_PROXY'] ?? '',
            $_ENV['HTTP_PROXY'] ?? '',
        ] as $p
    ) {
        $p = trim((string)$p);
        if ($p !== '') {
            return $p;
        }
    }

    return null;
}

function applyCurlProxy(\CurlHandle $ch, ?string $proxy): void
{
    if ($proxy === null) {
        return;
    }
    curl_setopt($ch, CURLOPT_PROXY, $proxy);
    if (preg_match('#^socks5h://#i', $proxy)) {
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5_HOSTNAME);
    } elseif (preg_match('#^socks5://#i', $proxy)) {
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
    }
}

function httpGetJson(string $url): ?array
{
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'MyOPCards/1.0 (sync-don-tcgplayer-ids)',
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ];
    curl_setopt_array($ch, $opts);
    applyCurlProxy($ch, scrapingProxy());
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($body === false || $code !== 200) {
        return null;
    }
    $j = json_decode($body, true);

    return is_array($j) ? $j : null;
}

/**
 * Marketplace search (One Piece + product type + query). Query string must mirror the site URL or filters are ignored.
 *
 * @return array{0: ?array, 1: int} [decoded JSON or null, HTTP status]
 */
function tcgplayerMpSearchPost(string $searchUrl, string $jsonBody): array
{
    $ch = curl_init($searchUrl);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $jsonBody,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; MyOPCards/1.0; +sync-don-tcgplayer-ids)',
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Content-Type: application/json',
            'Origin: https://www.tcgplayer.com',
            'Referer: https://www.tcgplayer.com/',
        ],
    ];
    curl_setopt_array($ch, $opts);
    applyCurlProxy($ch, scrapingProxy());
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($body === false) {
        return [null, $code];
    }
    $j = json_decode($body, true);

    return [is_array($j) ? $j : null, $code];
}

function normName(string $s): string
{
    $s = preg_replace('/\s+/u', ' ', trim($s));

    return mb_strtolower((string)$s);
}

/** Max page size accepted by mp-search-api (larger values return HTTP 400). */
const TCG_MP_SEARCH_MAX_PAGE = 50;

if (isset($opts['search'])) {
    $pageSize = min(TCG_MP_SEARCH_MAX_PAGE, max(1, (int)($opts['page-size'] ?? TCG_MP_SEARCH_MAX_PAGE)));
    $proxy = scrapingProxy();
    echo "Fetching DON!! via TCGPlayer mp-search-api (page size $pageSize, proxy: " . ($proxy ?? 'none') . ")\n";

    $baseQuery = http_build_query([
        'mpfev' => '0',
        'q' => 'Don!!',
        'productLineName' => 'one-piece-card-game',
        'setName' => 'product',
        'view' => 'grid',
    ]);
    $searchUrl = 'https://mp-search-api.tcgplayer.com/v1/search/product?' . $baseQuery;

    /** @var array<string, int> name(lower) => productId */
    $tcgDon = [];
    $from = 0;
    $totalResults = null;
    $pages = 0;

    while (true) {
        $body = json_encode([
            'from' => $from,
            'size' => $pageSize,
            'term' => 'Don!!',
        ], JSON_UNESCAPED_UNICODE);
        [$j, $http] = tcgplayerMpSearchPost($searchUrl, $body);
        if ($j === null || $http !== 200) {
            fwrite(STDERR, "mp-search-api failed (HTTP $http)\n");
            exit(1);
        }
        if (!empty($j['errors'])) {
            fwrite(STDERR, 'mp-search-api errors: ' . json_encode($j['errors'], JSON_UNESCAPED_UNICODE) . "\n");
            exit(1);
        }
        $block = $j['results'][0] ?? null;
        if (!is_array($block)) {
            fwrite(STDERR, "Unexpected mp-search-api response shape\n");
            exit(1);
        }
        if ($totalResults === null) {
            $totalResults = (int)($block['totalResults'] ?? 0);
            echo "Total matching products (TCGPlayer): $totalResults\n";
        }
        $rows = $block['results'] ?? [];
        if ($rows === []) {
            break;
        }
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            if (($row['productLineName'] ?? '') !== 'One Piece Card Game') {
                continue;
            }
            $name = trim((string)($row['productName'] ?? ''));
            $pid = (int)($row['productId'] ?? 0);
            if ($name === '' || $pid <= 0) {
                continue;
            }
            if (stripos($name, 'DON!!') === false && stripos($name, 'DON!') === false) {
                continue;
            }
            $key = normName($name);
            $tcgDon[$key] = $pid;
        }
        $from += count($rows);
        $pages++;
        if ($from >= $totalResults || count($rows) < $pageSize) {
            break;
        }
        if ($sleepUs > 0) {
            usleep($sleepUs);
        }
    }

    echo "Fetched $pages search page(s), " . count($tcgDon) . " One Piece DON name → id mappings.\n";
} else {
    $from = max(1, (int)($opts['from'] ?? 0));
    $to = max($from, (int)($opts['to'] ?? 0));
    if ($from === 0 || $to === 0) {
        fwrite(STDERR, "Both --from and --to are required with --scan\n");
        exit(1);
    }

    echo "Scanning TCGPlayer product IDs $from … $to (proxy: " . (scrapingProxy() ?? 'none') . ")\n";

    /** @var array<string, int> name(lower) => productId */
    $tcgDon = [];
    $n = 0;
    for ($id = $from; $id <= $to; $id++) {
        $j = httpGetJson('https://infinite-api.tcgplayer.com/product/' . $id . '?mpfev=0');
        if ($j === null) {
            if ($sleepUs > 0) {
                usleep($sleepUs);
            }
            continue;
        }
        $r = $j['result'] ?? null;
        if (!is_array($r)) {
            if ($sleepUs > 0) {
                usleep($sleepUs);
            }
            continue;
        }
        $name = trim((string)($r['name'] ?? ''));
        $line = trim((string)($r['productLine'] ?? ''));
        if ($name === '' || stripos($line, 'One Piece') === false) {
            if ($sleepUs > 0) {
                usleep($sleepUs);
            }
            continue;
        }
        if (stripos($name, 'DON!!') === false && stripos($name, 'DON!') === false) {
            if ($sleepUs > 0) {
                usleep($sleepUs);
            }
            continue;
        }
        $key = normName($name);
        $tcgDon[$key] = $id;
        $n++;
        if ($n % 25 === 0) {
            echo "  … $n DON rows (last id $id)\n";
        }
        if ($sleepUs > 0) {
            usleep($sleepUs);
        }
    }

    echo "Found $n One Piece DON!! product rows in range.\n";
}

$donCards = httpGetJson('https://optcgapi.com/api/allDonCards/');
if (!is_array($donCards)) {
    fwrite(STDERR, "Failed to fetch optcgapi allDonCards\n");
    exit(1);
}

$outPath = BASE_PATH . '/config/don_tcgplayer_product_ids.json';
$existing = [];
if (is_readable($outPath)) {
    $ex = json_decode((string)file_get_contents($outPath), true);
    if (is_array($ex)) {
        foreach ($ex as $k => $v) {
            if (preg_match('/^don_\d+$/i', (string)$k)) {
                $existing[(string)$k] = $v;
            }
        }
    }
}

$matched = 0;
$miss = [];
$map = $existing;
$donTotal = 0;

foreach ($donCards as $card) {
    $cid = trim((string)($card['card_image_id'] ?? $card['don_id'] ?? ''));
    if ($cid === '' || !preg_match('/^don_\d+$/i', $cid)) {
        continue;
    }
    $donTotal++;
    $cname = trim((string)($card['card_name'] ?? ''));
    $nk = normName($cname);
    if (isset($tcgDon[$nk])) {
        $map[$cid] = $tcgDon[$nk];
        $matched++;
    } else {
        $miss[] = $cid . "\t" . $cname;
    }
}

ksort($map, SORT_NATURAL | SORT_FLAG_CASE);

echo "Matched $matched / $donTotal DON cards to TCGPlayer IDs (" . count($miss) . " unmatched).\n";

if ($miss !== []) {
    $missPath = BASE_PATH . '/storage/cache/don_tcgplayer_unmatched.txt';
    @file_put_contents($missPath, implode("\n", $miss) . "\n");
    echo "Unmatched list: $missPath\n";
}

if ($dryRun) {
    echo "Dry-run: not writing JSON.\n";
    exit(0);
}

$json = json_encode($map, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
if (file_put_contents($outPath, $json) === false) {
    fwrite(STDERR, "Failed to write $outPath\n");
    exit(1);
}

echo "Wrote " . count($map) . " entries to $outPath\n";
