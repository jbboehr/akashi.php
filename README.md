![Probatio Verborum Viventium『証』〜ＡＫＡＳＨＩ〜 — executable documentation testing for PHP](https://jbboehr.github.io/akashi.php/images/akashi-banner.webp)

# Akashi

[![Build](https://github.com/jbboehr/akashi.php/actions/workflows/ci.yml/badge.svg)](https://github.com/jbboehr/akashi.php/actions/workflows/ci.yml)
[![Built with Nix](https://img.shields.io/badge/built%20with-Nix-5277C3?logo=nixos&logoColor=white)](https://github.com/jbboehr/akashi.php/blob/master/flake.nix)
[![License: AGPL-3.0-only WITH romic-exception](https://img.shields.io/badge/license-AGPL--3.0--only%20WITH%20romic--exception-blue.svg)](LICENSE.md)
[![AI burn](https://img.shields.io/endpoint?url=https%3A%2F%2Fgist.githubusercontent.com%2Fjbboehr%2F48eea04b7a73a84c397af8b9dc557556%2Fraw%2Fagent-badge.json&cacheSeconds=300)](https://github.com/arlegotin/agent-badge)

Akashi turns PHP examples in Markdown and PHPDoc into executable tests. PHP fences in a README, documentation site, or
source docblock form one shared corpus that PHPUnit executes in-process by default, while individual examples can opt
into a child process. Supported native `assert()` calls remain unconditional, failures point back to the maintained
source, and the same examples can participate in PHPStan verification or named consumer-fixture extraction.

Put an ordinary PHP fence in `README.md` or another selected Markdown file:

```php
$result = strtoupper('akashi');

assert($result === 'AKASHI');
```

This example is tested by Akashi in this repository.

## Quick PHPUnit Usage

Create a PHPUnit test such as `tests/DocumentationExamplesTest.php`:

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

Run `vendor/bin/phpunit`. Akashi discovers each selected PHP fence, rewrites supported native `assert()` calls so they
cannot be disabled by PHP configuration, and executes each example as a named PHPUnit data set. The trait supplies the
provider and test method; the project supplies only its corpus. The default backend isolates local variables and
declarations in-process. When an assertion fails, the report identifies the maintained documentation example rather than
only generated code.

## Installation

Akashi requires PHP 8.1 or later. Install it with a compatible PHPUnit release:

```console
composer require --dev "jbboehr/akashi:^0.2" "phpunit/phpunit:^10.5 || ^11.5"
```

Akashi supports the PHPUnit 10.5 and 11.5 release lines. Composer selects PHPUnit 10.5 on PHP 8.1 and the newest
compatible release on later PHP versions.

## Features

- Markdown PHP examples as named PHPUnit tests
- inline PHPDoc examples and references to canonical PHP files or named regions
- fast in-process execution by default
- opt-in child-process execution for examples that need process isolation
- unconditional documentation assertions
- expected throwable and exact stdout contracts
- source-aware parse, execution, assertion, and PHPStan failures
- one reusable example corpus for runtime and static-analysis verification
- stable `example` identities and legacy-marker compatibility for consumer-fixture extraction
- check or atomically update rendered copies from canonical PHP sources, with in-memory corrections and source-labelled
  unified diffs
- optional PHP-CS-Fixer checks for PHP embedded in Markdown and PHPDoc

Akashi executes trusted project code; neither runtime backend is a security sandbox. See
[Compatibility and Safety](https://jbboehr.github.io/akashi.php/reference/compatibility.html) for the exact boundary.

## Documentation and Status

Start with the [Quick Start](https://jbboehr.github.io/akashi.php/quick-start.html), or read the
[complete documentation](https://jbboehr.github.io/akashi.php/).

The Markdown, inline PHPDoc, canonical external-example, synchronization, and optional inline-formatting workflows, both
runtime backends, PHPUnit integration, PHPStan verification, and marked extraction are implemented. Both recorded
consumer migrations are complete. Akashi is still pre-1.0; its categorized public API is usable but may change between
minor releases before 1.0.

## License

Akashi is licensed under `AGPL-3.0-only WITH romic-exception`. See [LICENSE.md](LICENSE.md) and the
[Romic Exception](docs/LICENSE_EXCEPTION.md). Contributions follow
[CONTRIBUTING.md](https://github.com/jbboehr/akashi.php/blob/master/CONTRIBUTING.md).
