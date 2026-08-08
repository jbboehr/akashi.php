<?php

use Illuminate\Contracts\Cache\Store;

use function jbboehr\Yumemi\unit;

function cacheReportForOneMinute(Store $cache): void
{
    $cache->put('report', 'ready', unit(1, 'minute')); // PHPStan rejects minutes at this seconds boundary.
}
