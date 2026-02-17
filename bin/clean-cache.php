#!/usr/bin/env php
<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';

use App\Core\Cache;

echo "Cleaning expired cache entries...\n";

$count = Cache::cleanExpired();

echo "Cleaned {$count} expired cache entries.\n";