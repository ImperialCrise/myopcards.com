<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\HttpCache;

/**
 * DON!! cards: optcgapi has no card_image; official cardlist CDN has no don_* path.
 * Resolves artwork via optional sources:
 * 1) config/don_image_urls.json — manual map { "don_1": "https://..." }
 * 2) storage/cache/don_image_urls.cache.json — populated after remote resolve or redirect handler
 * 3) OPECards.fr HTML search (English card art, no API key) — search with language=EN
 * 4) TCGPlayer — config/don_tcgplayer_product_ids.json maps don_* → product id; image from infinite-api.tcgplayer.com JSON.
 *    Regenerate: php bin/sync-don-tcgplayer-ids.php --search (mp-search-api, same filters as the site DON!! search).
 *    Fetched TCGPlayer JPEGs are cached under MinIO key cards/don_*.jpg and/or public/uploads/cards/, then served as /uploads/cards/don_*.jpg.
 *
 * Scraping HTTP(S)/SOCKS proxy: SCRAPING_HTTP_PROXY (or TCGPLAYER_HTTP_PROXY, CARDMARKET_FLARE_CURL_PROXY, HTTP_PROXY).
 * OpenVPN (.ovpn in config/) is not loaded by PHP: run OpenVPN on the host so egress uses the VPN, or expose a local proxy and set SCRAPING_HTTP_PROXY.
 *
 * @see https://www.opecards.fr/cards/search?name=DON!!&language=EN
 * @see https://www.tcgplayer.com/product/655740/one-piece-card-game-premium-booster-the-best-vol-2-don-card-kalgara-gold
 */
final class DonCardImageResolver
{
    private const CACHE_FILE = 'don_image_urls.cache.json';

    private const OPECARDS_SEARCH = 'https://www.opecards.fr/cards/search';

    private const TCGPLAYER_API_TMPL = 'https://infinite-api.tcgplayer.com/product/%d?mpfev=0';

    /** don_1, don_130, don_19_manga, … (not don_abc — digits required after don_) */
    private const DON_CARD_SET_ID_RX = '/^don_\d+(?:_[a-z0-9]+)*$/i';

    public static function isDonCardSetId(string $cardSetId): bool
    {
        return (bool)preg_match(self::DON_CARD_SET_ID_RX, $cardSetId);
    }

    /**
     * Local /uploads/cards/don_*.jpg only counts as cached for this card when the basename matches card_set_id
     * (e.g. don_19_manga must use don_19_manga.jpg, not don_19.jpg left over from sync).
     */
    public static function isLocalDonUploadForCard(string $cardSetId, string $url): bool
    {
        if (!self::isDonCardSetId($cardSetId) || $url === '') {
            return false;
        }
        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            $path = $url;
        }
        if (!str_starts_with($path, '/uploads/cards/')) {
            return false;
        }
        $base = strtolower(basename($path));
        $id = strtolower($cardSetId);

        return $base === $id . '.jpg' || $base === $id . '.jpeg';
    }

    public static function placeholderPath(): string
    {
        return '/assets/img/don-card-placeholder.svg';
    }

    /** @return ?string Remote https URL, local placeholder path, or null = unknown */
    public static function resolve(string $cardSetId, string $cardName, bool $allowRemoteFetch = true, string $setNameHint = ''): ?string
    {
        if (!self::isDonCardSetId($cardSetId)) {
            return null;
        }

        $manual = self::loadJsonFile(\dirname(__DIR__, 2) . '/config/don_image_urls.json');
        if (!empty($manual[$cardSetId]) && filter_var($manual[$cardSetId], FILTER_VALIDATE_URL)) {
            return $manual[$cardSetId];
        }

        $tcgPid = self::tcgPlayerProductIdForDon($cardSetId);
        $cache = self::readCache();
        $cachedUrl = $cache[$cardSetId] ?? '';
        if ($cachedUrl !== '' && filter_var($cachedUrl, FILTER_VALIDATE_URL)) {
            $host = strtolower((string)(parse_url($cachedUrl, PHP_URL_HOST) ?? ''));
            $staleOpecards = $tcgPid !== null && self::tcgPlayerEnabled()
                && (str_contains($host, 'opecards.fr') || str_contains($host, 'opecards.'));
            if (!$staleOpecards) {
                return $cachedUrl;
            }
        }

        if (!$allowRemoteFetch) {
            return self::placeholderPath();
        }

        $fullHint = trim($cardName . ' ' . $setNameHint);

        if (self::tcgPlayerEnabled() && $tcgPid !== null) {
            self::throttleTcgPlayer();
            $remote = self::fetchImageUrlFromTcgPlayerProduct($tcgPid);
            if ($remote !== null) {
                self::writeCacheEntry($cardSetId, $remote);

                return $remote;
            }
        }

        if (self::opecardsEnabled()) {
            self::throttleOpecards();
            $remote = self::fetchImageUrlFromOpecards($fullHint);
            if ($remote !== null) {
                self::writeCacheEntry($cardSetId, $remote);

                return $remote;
            }
        }

        return self::placeholderPath();
    }

    public static function tcgPlayerEnabled(): bool
    {
        $v = strtolower(trim((string)($_ENV['DON_IMAGE_USE_TCGPLAYER'] ?? '1')));

        return $v !== '0' && $v !== 'false' && $v !== 'no';
    }

    public static function opecardsEnabled(): bool
    {
        $v = strtolower(trim((string)($_ENV['DON_IMAGE_USE_OPECARDS'] ?? '1')));

        return $v !== '0' && $v !== 'false' && $v !== 'no';
    }

    private static function fetchImageUrlFromOpecards(string $fullHint): ?string
    {
        $fullHint = trim($fullHint);
        $codes = self::extractProductCodes($fullHint);
        $phases = self::opecardsSearchPhases($fullHint);
        $bestUrl = null;
        $bestScore = -1.0;

        foreach ($phases as $idx => $query) {
            $isBroadDon = (strcasecmp($query, 'DON!!') === 0);
            $maxPages = $isBroadDon ? 8 : 1;

            for ($page = 1; $page <= $maxPages; $page++) {
                $html = self::httpGetScrape(self::opecardsSearchUrl($query, $page), [
                    'Accept: text/html,application/xhtml+xml;q=0.9,*/*;q=0.8',
                    'Accept-Language: en,en-US;q=0.9,fr;q=0.5',
                ]);
                if ($html === null || $html === '') {
                    break;
                }
                $items = self::parseOpecardsItemsFromHtml($html);
                if ($items === []) {
                    break;
                }
                foreach ($items as $it) {
                    $titleLower = mb_strtolower($it['title']);
                    if (!self::opecardsSubjectSegmentsAllMatch($fullHint, $titleLower)) {
                        continue;
                    }
                    $score = self::opecardsMatchScore($fullHint, $it['title'], $codes, $titleLower);
                    if ($score > $bestScore) {
                        $bestScore = $score;
                        $bestUrl = $it['image'];
                    }
                }
                if (!$isBroadDon && $bestScore >= 72.0) {
                    return $bestUrl;
                }
                if ($isBroadDon && count($items) < 10) {
                    break;
                }
            }

            if (!$isBroadDon && $bestScore >= 55.0 && $bestUrl !== null) {
                return $bestUrl;
            }
        }

        if ($bestUrl !== null && $bestScore >= 38.0) {
            return $bestUrl;
        }

        return null;
    }

    /** @return list<string> */
    private static function opecardsSearchPhases(string $fullHint): array
    {
        $out = [];
        if ($fullHint !== '' && preg_match_all('/\(([^)]+)\)/u', $fullHint, $m)) {
            foreach ($m[1] as $inner) {
                $t = trim($inner);
                if ($t === '' || preg_match('/^DON!!(\s*Card)?$/iu', $t)) {
                    continue;
                }
                if (mb_strlen($t) >= 2) {
                    $out[] = $t;
                }
            }
        }
        if ($fullHint !== '' && preg_match('#//\s*(.+)$#u', $fullHint, $m)) {
            $t = trim($m[1]);
            if (mb_strlen($t) >= 3) {
                $out[] = $t;
            }
        }
        foreach (self::extractProductCodes($fullHint) as $code) {
            $out[] = $code;
        }
        $out[] = 'DON!!';

        $seen = [];
        $uniq = [];
        $noise = ['gold', 'silver', 'foil textured', 'alternate art', 'full art', 'manga', 'parallel', '3d text', 'art', 'card'];
        foreach ($out as $q) {
            $k = mb_strtolower(trim($q));
            if ($k === '' || isset($seen[$k])) {
                continue;
            }
            if ($k !== 'don!!' && in_array($k, $noise, true)) {
                continue;
            }
            $seen[$k] = true;
            $uniq[] = $q;
        }

        return $uniq;
    }

    /** @return list<string> */
    private static function extractProductCodes(string $text): array
    {
        if ($text === '') {
            return [];
        }
        // Normalize "PRB-02", "OP-14" so the same regex matches set codes in optcg strings.
        $norm = preg_replace('/\b(OP|PRB|ST|EB)-(\d{1,3})\b/iu', '$1$2', $text);
        $norm = is_string($norm) ? $norm : $text;
        if (!preg_match_all('/\b(OP\d{1,3}|PRB\d{2}|ST\d{1,3}|EB\d{2}|OPDD|PCC-[A-Z0-9]+|ONR\d{4}|OFFR\d{4})\b/iu', $norm, $m)) {
            return [];
        }
        $codes = [];
        foreach ($m[0] as $c) {
            $codes[mb_strtoupper($c)] = true;
        }

        return array_keys($codes);
    }

    private static function opecardsSearchUrl(string $nameQuery, int $page): string
    {
        $q = [
            'name' => $nameQuery,
            'language' => 'EN',
        ];
        if ($page > 1) {
            $q['page'] = $page;
        }

        return self::OPECARDS_SEARCH . '?' . http_build_query($q, '', '&', PHP_QUERY_RFC3986);
    }

    /** HTTP(S) or SOCKS proxy for OPECards + TCGPlayer fetches (VPN sidecar / Gluetun / Nord SOCKS). */
    private static function scrapingHttpProxy(): ?string
    {
        $candidates = [
            $_ENV['SCRAPING_HTTP_PROXY'] ?? '',
            $_ENV['TCGPLAYER_HTTP_PROXY'] ?? '',
            $_ENV['CARDMARKET_FLARE_CURL_PROXY'] ?? '',
            $_ENV['HTTP_PROXY'] ?? '',
        ];
        foreach ($candidates as $p) {
            $p = trim((string)$p);
            if ($p !== '') {
                return $p;
            }
        }

        return null;
    }

    /**
     * @param list<string> $headers
     */
    private static function httpGetScrape(string $url, array $headers = []): ?string
    {
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'MyOPCards/1.0 (+don-image; respectful fetch)',
        ];
        if ($headers !== []) {
            $opts[CURLOPT_HTTPHEADER] = $headers;
        }
        $proxy = self::scrapingHttpProxy();
        if ($proxy !== null) {
            $opts[CURLOPT_PROXY] = $proxy;
            if (preg_match('#^socks5h://#i', $proxy)) {
                $opts[CURLOPT_PROXYTYPE] = CURLPROXY_SOCKS5_HOSTNAME;
            } elseif (preg_match('#^socks5://#i', $proxy)) {
                $opts[CURLOPT_PROXYTYPE] = CURLPROXY_SOCKS5;
            }
        }
        curl_setopt_array($ch, $opts);
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body === false || $code !== 200) {
            return null;
        }

        return $body;
    }

    private static function tcgPlayerProductIdForDon(string $cardSetId): ?int
    {
        $path = \dirname(__DIR__, 2) . '/config/don_tcgplayer_product_ids.json';
        $map = self::loadJsonFile($path);
        $raw = $map[$cardSetId] ?? null;
        if ($raw === null && preg_match('/^(don_\d+)_[a-z0-9_]+$/i', $cardSetId, $m)) {
            $raw = $map[$m[1]] ?? null;
        }
        if ($raw === null || $raw === '') {
            return null;
        }

        return self::normalizeTcgPlayerProductId($raw);
    }

    private static function normalizeTcgPlayerProductId(mixed $v): ?int
    {
        if (is_int($v) && $v > 0) {
            return $v;
        }
        if (is_string($v)) {
            $s = trim($v);
            if ($s !== '' && preg_match('/^\d+$/', $s)) {
                return (int)$s;
            }
            if (preg_match('#tcgplayer\.com/product/(\d+)#i', $s, $m)) {
                return (int)$m[1];
            }
        }

        return null;
    }

    private static function fetchImageUrlFromTcgPlayerProduct(int $productId): ?string
    {
        if ($productId <= 0) {
            return null;
        }
        $url = sprintf(self::TCGPLAYER_API_TMPL, $productId);
        $body = self::httpGetScrape($url, [
            'Accept: application/json',
        ]);
        if ($body === null || $body === '') {
            return null;
        }
        $json = json_decode($body, true);
        if (!is_array($json)) {
            return null;
        }
        $result = $json['result'] ?? null;
        if (!is_array($result)) {
            return null;
        }
        $img = $result['tcgImageURL'] ?? null;
        if (!is_string($img) || !filter_var($img, FILTER_VALIDATE_URL)) {
            return null;
        }
        if (!str_starts_with($img, 'https://')) {
            return null;
        }

        // Keep API _200w URL: _400w is often blocked for server-side download; fetch tries both widths anyway.
        return $img;
    }

    /**
     * @return list<array{title: string, image: string}>
     */
    private static function parseOpecardsItemsFromHtml(string $html): array
    {
        $prev = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $xp = new \DOMXPath($dom);
        $nodes = $xp->query("//div[contains(concat(' ', normalize-space(@class), ' '), ' item ')][@data-item]");
        if ($nodes === false) {
            return [];
        }

        $items = [];
        foreach ($nodes as $node) {
            if (!$node instanceof \DOMElement) {
                continue;
            }
            $title = '';
            $h3 = $xp->query(".//h3[contains(concat(' ', normalize-space(@class), ' '), ' item-name ')]", $node)->item(0);
            if ($h3 instanceof \DOMElement) {
                $title = trim($h3->getAttribute('title') ?: $h3->textContent);
            }
            if ($title === '') {
                $a = $xp->query(".//a[contains(@class, 'item-main-link')]", $node)->item(0);
                if ($a instanceof \DOMElement) {
                    $title = trim($a->getAttribute('title') ?: $a->getAttribute('aria-label') ?: '');
                }
            }

            $image = '';
            foreach ($xp->query('.//img[@data-src]', $node) as $img) {
                if (!$img instanceof \DOMElement) {
                    continue;
                }
                $ds = trim($img->getAttribute('data-src'));
                if ($ds === '' || !str_starts_with($ds, 'https://static.opecards.fr/cards/en/')) {
                    continue;
                }
                if (str_contains($ds, 'back-don')) {
                    continue;
                }
                $image = $ds;
                break;
            }
            if ($title === '' || $image === '') {
                continue;
            }
            $pathOnly = (string)(parse_url($image, PHP_URL_PATH) ?? '');
            if (!str_contains(mb_strtoupper($title), 'DON!!') && !preg_match('~(^|[/-])don[-.]~i', $pathOnly)) {
                continue;
            }
            $items[] = ['title' => $title, 'image' => $image];
        }

        return $items;
    }

    private static function opecardsSubjectSegmentsAllMatch(string $fullHint, string $titleLower): bool
    {
        $segments = self::opecardsSubjectSegments($fullHint);
        if ($segments === []) {
            return true;
        }
        foreach ($segments as $seg) {
            $s = mb_strtolower(trim($seg));
            if (mb_strlen($s) < 3) {
                continue;
            }
            if (!self::opecardsNameFragmentMatchesTitle($s, $titleLower)) {
                return false;
            }
        }

        return true;
    }

    private static function opecardsMatchScore(string $fullHint, string $opecTitle, array $codes, string $titleLower): float
    {
        $a = mb_strtolower(preg_replace('/\s+/u', ' ', trim($fullHint)));
        $b = $titleLower;
        if ($a === '' || $b === '') {
            return 0.0;
        }
        similar_text($a, $b, $pct);
        $score = $pct;
        foreach ($codes as $code) {
            $c = mb_strtolower($code);
            if ($c !== '' && (str_contains($a, $c) || str_contains($b, $c))) {
                $score += 12.0;
            }
        }
        if (str_contains($b, 'don!!')) {
            $score += 8.0;
        }

        foreach (self::opecardsSubjectSegments($fullHint) as $seg) {
            $s = mb_strtolower(trim($seg));
            if (mb_strlen($s) < 3) {
                continue;
            }
            if (self::opecardsNameFragmentMatchesTitle($s, $b)) {
                $score += 22.0;
            }
        }

        foreach (['double pack', 'foil textured', 'gold', 'alternative art', 'full art', 'jolly roger', 'manga', 'parallel', '3d text'] as $phrase) {
            $inA = str_contains($a, $phrase);
            $inB = str_contains($b, $phrase);
            if ($inA && $inB) {
                $score += 18.0;
            } elseif ($inA !== $inB) {
                $score -= 12.0;
            }
        }

        if (preg_match_all('/[\p{L}\p{N}]{4,}/u', $a, $tm)) {
            foreach ($tm[0] as $tok) {
                $tok = mb_strtolower($tok);
                if (str_contains($b, $tok)) {
                    $score += 5.0;
                }
            }
        }

        return $score;
    }

    /**
     * Parenthetical fragments that name the character / variant (not set-only boilerplate).
     *
     * @return list<string>
     */
    private static function opecardsSubjectSegments(string $fullHint): array
    {
        if ($fullHint === '' || !preg_match_all('/\(([^)]+)\)/u', $fullHint, $m)) {
            return [];
        }
        $out = [];
        $noise = ['gold', 'silver', 'foil textured', 'alternate art', 'full art', 'manga', 'parallel', '3d text', 'art', 'card', 'foil'];
        foreach ($m[1] as $inner) {
            $t = trim($inner);
            if ($t === '' || preg_match('/^DON!!(\s*Card)?$/iu', $t)) {
                continue;
            }
            if (preg_match('/^double\s+pack\b/iu', $t)) {
                continue;
            }
            if (mb_strlen($t) > 48) {
                continue;
            }
            if (in_array(mb_strtolower($t), $noise, true)) {
                continue;
            }
            $out[] = $t;
            if (count($out) >= 2) {
                break;
            }
        }

        return $out;
    }

    /** Match e.g. "gol.d.roger" to titles containing "gol d. roger" or "gol d roger". */
    private static function opecardsNameFragmentMatchesTitle(string $fragmentLower, string $titleLower): bool
    {
        if (str_contains($titleLower, $fragmentLower)) {
            return true;
        }
        $spaced = preg_replace('/\.+/u', ' ', $fragmentLower);
        $spaced = trim(preg_replace('/\s+/u', ' ', (string)$spaced));
        if ($spaced !== '' && str_contains($titleLower, $spaced)) {
            return true;
        }
        $compactFrag = preg_replace('/\s+/u', '', $fragmentLower);
        $compactTitle = preg_replace('/\s+/u', '', $titleLower);

        return $compactFrag !== '' && str_contains($compactTitle, $compactFrag);
    }

    /**
     * TCGPlayer image CDN often returns 403 when the browser loads it from another site (hotlink).
     * Those URLs must be served via /api/don-card-image (proxy), not stored for direct img hotlinks.
     */
    public static function isTcgplayerCdnImageUrl(string $url): bool
    {
        if ($url === '' || !str_starts_with($url, 'https://')) {
            return false;
        }
        $host = strtolower((string)(parse_url($url, PHP_URL_HOST) ?? ''));

        return $host === 'tcgplayer-cdn.tcgplayer.com' || $host === 'product-images.tcgplayer.com';
    }

    /**
     * Cached DON!! file basename (MinIO key suffix and public URL path), e.g. don_130.jpg.
     */
    private static function donImageCacheBasename(string $cardSetId): ?string
    {
        if (!self::isDonCardSetId($cardSetId)) {
            return null;
        }

        return strtolower($cardSetId) . '.jpg';
    }

    private static function donImageStorageKey(string $cardSetId): ?string
    {
        $base = self::donImageCacheBasename($cardSetId);

        return $base !== null ? 'cards/' . $base : null;
    }

    private static function donImageLocalPath(string $cardSetId): ?string
    {
        $base = self::donImageCacheBasename($cardSetId);
        if ($base === null) {
            return null;
        }

        return \dirname(__DIR__, 2) . '/public/uploads/cards/' . $base;
    }

    /** Public URL path served by UploadController::serveCards (MinIO or local disk). */
    public static function donImageCachedPublicPath(string $cardSetId): ?string
    {
        $base = self::donImageCacheBasename($cardSetId);
        if ($base === null) {
            return null;
        }

        return '/uploads/cards/' . $base;
    }

    public static function donImageIsCached(string $cardSetId): bool
    {
        $key = self::donImageStorageKey($cardSetId);
        if ($key !== null && StorageService::isConfigured() && StorageService::exists($key)) {
            return true;
        }
        $local = self::donImageLocalPath($cardSetId);

        return $local !== null && is_file($local) && is_readable($local);
    }

    /**
     * @return ?array{0: string, 1: string} [body, mime]
     */
    private static function tryDownloadTcgplayerCdnUrl(string $url): ?array
    {
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER => [
                'Accept: image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
                'Referer: https://www.tcgplayer.com/',
                'Origin: https://www.tcgplayer.com',
            ],
        ];
        $proxy = self::scrapingHttpProxy();
        if ($proxy !== null) {
            $opts[CURLOPT_PROXY] = $proxy;
            if (preg_match('#^socks5h://#i', $proxy)) {
                $opts[CURLOPT_PROXYTYPE] = CURLPROXY_SOCKS5_HOSTNAME;
            } elseif (preg_match('#^socks5://#i', $proxy)) {
                $opts[CURLOPT_PROXYTYPE] = CURLPROXY_SOCKS5;
            }
        }
        curl_setopt_array($ch, $opts);
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $ctype = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        if ($body === false || $code !== 200 || !is_string($body) || $body === '') {
            return null;
        }
        $mime = 'image/jpeg';
        if (is_string($ctype) && str_contains($ctype, 'image/')) {
            $mime = preg_replace('/;\s*.+$/', '', trim(explode(',', $ctype)[0])) ?: $mime;
        }
        if (!str_starts_with($mime, 'image/')) {
            $mime = 'image/jpeg';
        }

        return [$body, $mime];
    }

    /**
     * @return ?array{0: string, 1: string} [body, mime]
     */
    private static function fetchTcgPlayerImageFromUrl(string $url): ?array
    {
        if (!self::isTcgplayerCdnImageUrl($url)) {
            return null;
        }

        $urls = [$url];
        if (preg_match('#_400w\.(jpe?g)$#i', $url)) {
            $alt = preg_replace('#_400w\.(jpe?g)$#i', '_200w.$1', $url);
            if ($alt !== $url) {
                $urls[] = $alt;
            }
        } elseif (preg_match('#_200w\.(jpe?g)$#i', $url)) {
            $alt = preg_replace('#_200w\.(jpe?g)$#i', '_400w.$1', $url);
            if ($alt !== $url) {
                $urls[] = $alt;
            }
        }

        foreach (array_unique($urls) as $u) {
            $r = self::tryDownloadTcgplayerCdnUrl($u);
            if ($r !== null) {
                return $r;
            }
        }

        return null;
    }

    /**
     * Write DON!! JPEG to MinIO (if configured) and/or local public/uploads/cards (fallback or when no MinIO).
     */
    public static function writeDonImageCache(string $cardSetId, string $jpegBody, string $mime = 'image/jpeg'): bool
    {
        if ($jpegBody === '') {
            return false;
        }
        $key = self::donImageStorageKey($cardSetId);
        if ($key === null) {
            return false;
        }
        if (StorageService::isConfigured() && StorageService::put($key, $jpegBody, $mime)) {
            return true;
        }

        $local = self::donImageLocalPath($cardSetId);
        if ($local === null) {
            return false;
        }
        $dir = \dirname($local);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        return @file_put_contents($local, $jpegBody) !== false;
    }

    /**
     * Cache hit: redirect to static /uploads/cards/don_*.jpg. Miss: fetch TCGPlayer, persist to disk/MinIO, stream once.
     */
    public static function serveTcgPlayerDonImage(string $cardSetId, string $sourceUrl): bool
    {
        if (!self::isDonCardSetId($cardSetId) || !self::isTcgplayerCdnImageUrl($sourceUrl)) {
            return false;
        }

        $publicPath = self::donImageCachedPublicPath($cardSetId);
        if ($publicPath === null) {
            return false;
        }

        if (self::donImageIsCached($cardSetId)) {
            header('Cache-Control: ' . HttpCache::CARD_IMAGE_IMMUTABLE);
            header('Location: ' . $publicPath, true, 302);

            return true;
        }

        $fetched = self::fetchTcgPlayerImageFromUrl($sourceUrl);
        if ($fetched === null) {
            return false;
        }
        [$body, $mime] = $fetched;
        if (self::writeDonImageCache($cardSetId, $body, $mime)) {
            self::persistDonCardImagePath($cardSetId, $publicPath);
        }

        header('Content-Type: ' . $mime);
        header('Cache-Control: ' . HttpCache::CARD_IMAGE_IMMUTABLE);
        header('Content-Length: ' . (string)strlen($body));
        echo $body;

        return true;
    }

    /**
     * Persist /uploads/cards/don_*.jpg or remote URL (not TCGPlayer CDN) for card_image_url.
     */
    public static function persistDonCardImagePath(string $cardSetId, string $pathOrUrl): void
    {
        if (!self::isDonCardSetId($cardSetId)) {
            return;
        }
        $pathOrUrl = trim($pathOrUrl);
        if ($pathOrUrl === '') {
            return;
        }
        if (str_starts_with($pathOrUrl, '/uploads/cards/don_')
            && preg_match('#^/uploads/cards/don_\d+(?:_[a-z0-9]+)*\.jpe?g$#i', $pathOrUrl)) {
            // ok
        } elseif (filter_var($pathOrUrl, FILTER_VALIDATE_URL)) {
            if (self::isTcgplayerCdnImageUrl($pathOrUrl)) {
                return;
            }
        } else {
            return;
        }
        try {
            $db = \App\Core\Database::getConnection();
            $stmt = $db->prepare('UPDATE cards SET card_image_url = :u WHERE card_set_id = :id');
            $stmt->execute(['u' => $pathOrUrl, 'id' => $cardSetId]);
        } catch (\Throwable) {
            // ignore
        }
    }

    public static function persistToDatabase(string $cardSetId, string $imageUrl): void
    {
        if (!self::isDonCardSetId($cardSetId)) {
            return;
        }
        if (str_starts_with($imageUrl, '/uploads/cards/don_')) {
            self::persistDonCardImagePath($cardSetId, $imageUrl);

            return;
        }
        if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            return;
        }
        if (self::isTcgplayerCdnImageUrl($imageUrl)) {
            return;
        }
        try {
            $db = \App\Core\Database::getConnection();
            $stmt = $db->prepare('UPDATE cards SET card_image_url = :u WHERE card_set_id = :id');
            $stmt->execute(['u' => $imageUrl, 'id' => $cardSetId]);
        } catch (\Throwable) {
            // ignore
        }
    }

    private static function readCache(): array
    {
        $path = self::cachePath();
        if (!is_readable($path)) {
            return [];
        }

        return self::loadJsonFile($path);
    }

    private static function writeCacheEntry(string $cardSetId, string $imageUrl): void
    {
        $path = self::cachePath();
        $dir = \dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $data = self::readCache();
        $data[$cardSetId] = $imageUrl;
        @file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private static function cachePath(): string
    {
        return \dirname(__DIR__, 2) . '/storage/cache/' . self::CACHE_FILE;
    }

    private static function loadJsonFile(string $path): array
    {
        if (!is_readable($path)) {
            return [];
        }
        $j = json_decode((string)file_get_contents($path), true);

        return is_array($j) ? $j : [];
    }

    /** Be polite to OPECards.fr (HTML search, no official API). */
    private static function throttleOpecards(): void
    {
        $path = \dirname(__DIR__, 2) . '/storage/cache/don_opecards_throttle.txt';
        $minInterval = 1.1;
        $last = 0.0;
        if (is_readable($path)) {
            $last = (float)trim((string)file_get_contents($path));
        }
        $now = microtime(true);
        $wait = $minInterval - ($now - $last);
        if ($wait > 0) {
            usleep((int)($wait * 1_000_000));
        }
        @file_put_contents($path, (string)microtime(true));
    }

    private static function throttleTcgPlayer(): void
    {
        $path = \dirname(__DIR__, 2) . '/storage/cache/don_tcgplayer_throttle.txt';
        $minInterval = 1.0;
        $last = 0.0;
        if (is_readable($path)) {
            $last = (float)trim((string)file_get_contents($path));
        }
        $now = microtime(true);
        $wait = $minInterval - ($now - $last);
        if ($wait > 0) {
            usleep((int)($wait * 1_000_000));
        }
        @file_put_contents($path, (string)microtime(true));
    }
}
