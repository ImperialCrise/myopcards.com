<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Card artwork for a given card_set_id / URL is permanent (Bandai does not replace files in place).
 * Browsers honor max-age; "immutable" skips revalidation while the entry is fresh.
 */
final class HttpCache
{
    public const CARD_IMAGE_IMMUTABLE = 'public, max-age=31536000, immutable';
}
