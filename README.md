![Probatio Verborum Viventium『証』〜ＡＫＡＳＨＩ〜 — executable documentation testing for PHP](docs/pages/images/akashi-banner.png)

# Akashi

[![Build](https://github.com/jbboehr/akashi.php/actions/workflows/ci.yml/badge.svg)](https://github.com/jbboehr/akashi.php/actions/workflows/ci.yml)
[![Built with Nix](https://img.shields.io/badge/built%20with-Nix-5277C3?logo=nixos&logoColor=white)](flake.nix)
[![License: AGPL-3.0-only WITH romic-exception](https://img.shields.io/badge/license-AGPL--3.0--only%20WITH%20romic--exception-blue.svg)](LICENSE.md)
[![AI burn](https://img.shields.io/endpoint?url=https%3A%2F%2Fgist.githubusercontent.com%2Fjbboehr%2F48eea04b7a73a84c397af8b9dc557556%2Fraw%2Fagent-badge.json&cacheSeconds=300)](https://github.com/arlegotin/agent-badge)

Akashi turns PHP examples in Markdown documentation into executable tests. Write normal PHP examples in a README or
documentation site, discover them as one corpus, and verify them through PHPUnit. Akashi runs examples in-process by
default, reports failures against their documentation locations, and can reuse the same examples for PHPStan checks or
explicit consumer-fixture extraction.

Put an ordinary PHP fence in `README.md` or another selected Markdown file:

```php
$result = strtoupper('akashi');

assert($result === 'AKASHI');
```

This example is tested by Akashi in this repository.

## Installation

Akashi requires PHP 8.2 or later. Until the first tagged release, install the development branch with PHPUnit:

```console
composer require --dev jbboehr/akashi:dev-master phpunit/phpunit:^11.5
```

## Quick PHPUnit Usage

Create a PHPUnit test such as `tests/DocumentationExamplesTest.php`:

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

Run `vendor/bin/phpunit`. Akashi discovers each selected PHP fence, rewrites supported native `assert()` calls so they
cannot be disabled by PHP configuration, and executes each example as a named PHPUnit data set. The trait supplies the
provider and test method; the project supplies only its corpus. The default backend isolates local variables and
declarations in-process. When an assertion fails, the report identifies the maintained Markdown example rather than only
generated code.

## Features

- Markdown PHP examples as named PHPUnit tests
- fast in-process execution by default
- opt-in child-process execution for examples that need process isolation
- unconditional documentation assertions
- source-aware parse, execution, assertion, and PHPStan failures
- one reusable example corpus for runtime and static-analysis verification
- configurable markers for stable consumer-fixture extraction

Akashi executes trusted project code; neither runtime backend is a security sandbox. See
[Compatibility and Safety](docs/pages/reference/compatibility.md) for the exact boundary.

## Documentation and Status

Start with the [Quick Start](docs/pages/quick-start.md), or read the
[complete documentation](https://jbboehr.github.io/akashi.php/).

The Markdown workflow, both runtime backends, PHPUnit integration, PHPStan verification, and marked extraction are
implemented and in active use. Both recorded consumer migrations are complete. Akashi is still pre-1.0, and its public
API remains provisional while it receives a pre-release stability review.

## License

Akashi is licensed under `AGPL-3.0-only WITH romic-exception`. See [LICENSE.md](LICENSE.md) and the
[Romic Exception](docs/LICENSE_EXCEPTION.md). Contributions follow [CONTRIBUTING.md](CONTRIBUTING.md).
