<?php

declare(strict_types=1);

if (!function_exists('t')) {
    function t(string $key, array|string $replace = []): string
    {
        return \App\Services\Lang::get($key, is_array($replace) ? $replace : [], is_string($replace) ? $replace : null);
    }
}

if (!function_exists('avatar_url')) {
    function avatar_url(array $user): string
    {
        return \App\Models\User::getAvatarUrl($user);
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return \App\Core\Auth::csrfToken();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('card_img_url')) {
    function card_img_url(array $card): string
    {
        $url = $card['card_image_url'] ?? '';
        $csi = (string)($card['card_set_id'] ?? '');
        $ctype = (string)($card['card_type'] ?? '');
        $isDon = $ctype === 'DON!!' || \App\Services\DonCardImageResolver::isDonCardSetId($csi);
        $isTcgplayerCdnDonImg = $url !== ''
            && (str_contains($url, 'tcgplayer-cdn.tcgplayer.com') || str_contains($url, 'product-images.tcgplayer.com'));
        $isCachedDonFile = \App\Services\DonCardImageResolver::isLocalDonUploadForCard($csi, $url);
        $wrongLocalDon = $url !== ''
            && str_starts_with($url, '/')
            && str_contains($url, '/uploads/cards/don_')
            && !\App\Services\DonCardImageResolver::isLocalDonUploadForCard($csi, $url);
        if ($isDon && !$isCachedDonFile && ($url === '' || str_contains($url, 'don-card-placeholder.svg') || $isTcgplayerCdnDonImg || $wrongLocalDon)) {
            $q = [
                'i' => $csi,
                'n' => $card['card_name'] ?? '',
            ];
            $sn = trim((string)($card['set_name'] ?? ''));
            if ($sn !== '') {
                $q['s'] = $sn;
            }

            return '/api/don-card-image?' . http_build_query($q);
        }
        if ($url === '') {
            return \App\Services\CardSyncService::officialCardlistProxyUrl($csi) ?? '';
        }
        $host = parse_url($url, PHP_URL_HOST) ?? '';
        if ($host === 'optcgapi.com' || $host === 'www.optcgapi.com') {
            return '/uploads/cards/' . basename(parse_url($url, PHP_URL_PATH));
        }

        return \App\Services\CardSyncService::rewriteOfficialCardlistUrlToProxy($url);
    }
}

/** OPTCG rules text — same keyword styling as game hover (board.js formatCardText). */
if (!function_exists('format_card_rules_html')) {
    function format_card_rules_html(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }
        $s = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $s = preg_replace('/\[(Rush|Blocker|Double Attack|Banish|On K\.O\.)\]/', '<span class="kw-badge kw-red">$1</span>', $s);
        $s = preg_replace('/\[(Trigger)\]/', '<span class="kw-badge kw-yellow">$1</span>', $s);
        $s = preg_replace('/\[(On Play|When Attacking)\]/', '<span class="kw-badge kw-blue">$1</span>', $s);
        $s = preg_replace('/\[(Activate: Main)\]/', '<span class="kw-badge kw-green">$1</span>', $s);
        $s = preg_replace('/\[(Counter)\]/', '<span class="kw-badge kw-purple">$1</span>', $s);
        $s = preg_replace('/\[(Once Per Turn)\]/', '<span class="kw-badge kw-gray">$1</span>', $s);
        $s = preg_replace('/\[(Your Turn|End of Your Turn|Opponent\'s Turn)\]/', '<span class="kw-badge kw-gray">$1</span>', $s);
        $s = str_replace('DON!!', '<span class="don-inline">DON!!</span>', $s);
        $s = preg_replace('/(\+\d+000)/', '<span class="pow-inline">$1</span>', $s);

        return nl2br($s, false);
    }
}


if (!function_exists('asset_v')) {
    function asset_v(string $path): string
    {
        $abs = ($_SERVER['DOCUMENT_ROOT'] ?? '') . $path;
        $ts = file_exists($abs) ? filemtime($abs) : time();
        return $path . '?v=' . $ts;
    }
}
