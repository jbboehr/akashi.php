# Quick Start

<figure class="logion" data-logion="OSD 18:2">
<div class="logion-text">
<blockquote>
<p>Above the unformed marsh, thunder wandered without echo until it entered a hollow bone. The bone answered, and reeds
lifted from the mud to hear. Thereafter every creature carried an emptiness by which the world might speak. Guard the
hollow within thee; abundance is not its only purpose.</p>
</blockquote>
<p class="logion-citation">— <cite>Ordinances of the Synthetic Dawn 18:2</cite></p>
</div>
<img src="images/logia/OSD-18_2.webp" alt="A hollow bone answering blue lightning in a dark marsh beneath amber celestial geometry" width="960" height="540" loading="eager" fetchpriority="high">
</figure>

This tutorial takes one Markdown example from source text to a named PHPUnit test. In-process execution is the default;
you do not need to configure an execution backend for this path.

## 1. Install Akashi and PHPUnit

Akashi requires PHP 8.1 or later. Install it with a compatible PHPUnit release:

```console
composer require --dev "jbboehr/akashi:^0.2" "phpunit/phpunit:^10.5 || ^11.5"
```

Akashi supports PHPUnit 10.5 and 11.5. Composer selects PHPUnit 10.5 on PHP 8.1 and the newest compatible release on
later PHP versions; this tutorial works with either line.

## 2. Write an Example

Add a PHP fence to `README.md`:

```php
$result = strtoupper('akashi');

assert($result === 'AKASHI');
```

An opening `<?php` tag is optional. Every fence whose first info-string word is `php`, compared case-insensitively,
enters the selected corpus.

## 3. Connect the Document to PHPUnit

Create `tests/DocumentationExamplesTest.php`:

<!-- akashi: compile-only -->

```php
<?php

use jbboehr\Akashi\ExampleCorpus;
use jbboehr\Akashi\Integration\PhpUnit\VerifiesPhpUnitExamples;
use jbboehr\Akashi\Source\DocumentationSource;
use PHPUnit\Framework\TestCase;

final class DocumentationExamplesTest extends TestCase
{
    use VerifiesPhpUnitExamples;

    protected static function akashiExampleCorpus(): ExampleCorpus
    {
        return DocumentationSource::forProject(dirname(__DIR__))
            ->withFile('README.md')
            ->load();
    }
}
```

For a test class directly inside `tests/`, `dirname(__DIR__)` resolves the project root independently of PHPUnit's
working directory. The trait supplies the PHPUnit data provider and test method. Your test class supplies the corpus.

## 4. Run It

```console
vendor/bin/phpunit
```

Akashi discovers the fence and gives it a deterministic, readable data-set label. The trait delegates to
`PhpUnitRuntime`, which transforms and executes the example in-process. The PHPUnit process's existing Composer
autoloader remains available to the example.

## 5. Break It Deliberately

Change the expected value to `Akashi` and run PHPUnit again. The test fails with the example ID, label, and originating
`README.md` line. Restore `AKASHI` to make it pass.

Akashi rewrites supported native `assert()` calls to PHPUnit assertions, so the check still runs when the host has
`zend.assertions=-1`. Details and edge cases are in [PHPUnit](using/phpunit.md#assertion-behavior).

## Where Next?

- [Authoring Examples](using/authoring.md) covers directories, exclusions, fence rules, and labels.
- [PHPUnit](using/phpunit.md) covers runtime configuration and result reporting.
- [PHPStan](using/phpstan.md) adds optional static-analysis verification of the same corpus.
- [Separate-Process Execution](using/separate-process.md) handles examples that cannot run safely in-process.
- [Extracting Named Examples](using/extracting.md) turns a marked fence into a stable consumer fixture.
- [Compatibility and Safety](reference/compatibility.md) records supported versions and exact limitations.

The documentation example in this tutorial executes through Akashi. The PHPUnit integration snippet receives
compile-only validation because its `__DIR__` is meaningful after copying it into the project's `tests/` directory.
