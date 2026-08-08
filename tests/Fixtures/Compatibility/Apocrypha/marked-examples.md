# Yumemi Apocrypha marked examples

<!-- yumemi-example: readme-cache-invalid -->

```php
<?php

use Illuminate\Contracts\Cache\Store;

use function jbboehr\Yumemi\unit;

function cacheReportForOneMinute(Store $cache): void
{
    $cache->put('report', 'ready', unit(1, 'minute')); // PHPStan rejects minutes at this seconds boundary.
}
```

<!-- yumemi-example: getting-started-invalid -->

```php
<?php

use Illuminate\Contracts\Cache\Store;

use function jbboehr\Yumemi\unit;

function verifyApocryphaInstallation(Store $cache): void
{
    $cache->put('report', 'ready', unit(30, 'second'));
    $cache->put('report', 'stale', unit(1, 'minute')); // PHPStan rejects this seconds boundary.
}
```

<!-- yumemi-example: illuminate-cache-invalid -->

```php
<?php

use Illuminate\Cache\RateLimiter;

use function jbboehr\Yumemi\unit;

function recordCacheAttempt(RateLimiter $limiter): void
{
    $limiter->hit('report', unit(30, 'second'));
    $limiter->hit('report', unit(2, 'minute')); // PHPStan rejects minutes at this seconds boundary.
}
```

<!-- yumemi-example: illuminate-http-invalid -->

```php
<?php

use Illuminate\Http\Client\PendingRequest;

use function jbboehr\Yumemi\unit;

function configureRemoteArchive(PendingRequest $request): void
{
    $request->timeout(unit(30, 'second'));
    $request->timeout(unit(250, 'millisecond')); // PHPStan rejects milliseconds at this seconds boundary.
}
```

<!-- yumemi-example: symfony-stopwatch-invalid -->

```php
<?php

use Symfony\Component\Stopwatch\Stopwatch;

/** @param unit_int<'second'>|unit_float<'second'> $duration */
function recordProfileDurationInSeconds(int|float $duration): void {}

$event = (new Stopwatch())->start('render-report');

recordProfileDurationInSeconds($event->getDuration()); // PHPStan rejects milliseconds at this seconds boundary.
```

<!-- yumemi-example: guzzle-invalid -->

```php
<?php

use GuzzleHttp\Client;

use function jbboehr\Yumemi\unit;

function fetchRemoteReport(Client $client): void
{
    $client->request('GET', '/reports', ['timeout' => unit(2, 'second')]);
    $client->request('GET', '/reports', [
        'timeout' => unit(250, 'millisecond'), // PHPStan rejects milliseconds at this seconds boundary.
    ]);
}
```

<!-- yumemi-example: phpgeo-invalid -->

```php
<?php

use Location\Bearing\BearingSpherical;
use Location\Coordinate;

use function jbboehr\Yumemi\unit;

function projectSurveyPoint(BearingSpherical $bearing, Coordinate $origin): void
{
    $bearing->calculateDestination($origin, unit(45.0, 'degree'), unit(500.0, 'meter'));
    $bearing->calculateDestination(
        $origin,
        unit(0.5, 'radian'), // PHPStan rejects radians at this degree boundary.
        unit(500.0, 'meter'),
    );
}
```

<!-- yumemi-example: getid3-invalid -->

```php
<?php

use JamesHeinrich\GetID3\GetID3;

/** @param unit_int<'second'>|unit_float<'second'> $duration */
function recordMediaDuration(int|float $duration): void {}

$metadata = (new GetID3())->analyze('/srv/media/interview.wav');

if (isset($metadata['playtime_seconds'])) {
    recordMediaDuration($metadata['playtime_seconds']);
}

if (isset($metadata['bitrate'])) {
    recordMediaDuration($metadata['bitrate']); //! PHPStan rejects a bitrate at this duration boundary.
}
```
