# Quick Start

This tutorial takes one Markdown example from source text to a named PHPUnit test. In-process execution is the default;
you do not need to configure an execution backend for this path.

## 1. Install Akashi and PHPUnit

Akashi requires PHP 8.2 or later. Until the first tagged release, install its development branch:

```console
composer require --dev jbboehr/akashi:dev-master phpunit/phpunit:^11.5
```

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

```php
<?php

use jbboehr\Akashi\ExampleCorpus;
use jbboehr\Akashi\Integration\PhpUnit\VerifiesPhpUnitExamples;
use jbboehr\Akashi\Source\MarkdownSource;
use PHPUnit\Framework\TestCase;

final class DocumentationExamplesTest extends TestCase
{
    use VerifiesPhpUnitExamples;

    protected static function akashiExampleCorpus(): ExampleCorpus
    {
        return MarkdownSource::forProject(getcwd() ?: throw new RuntimeException('Project root unavailable.'))
            ->includeFile('README.md')
            ->load();
    }
}
```

This quick start assumes PHPUnit runs from the project root, the directory containing `composer.json`. If your test
command uses another working directory, replace `getcwd()` with a known absolute project-root path. The trait supplies
the PHPUnit data provider and test method. Your test class supplies the corpus.

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

The executable fences in this tutorial are included in Akashi's own documentation-example test.
