# Getting Started

Akashi targets PHP 8.2 and later.

## Installation

Until the first tagged release, install the development branch:

```shell
composer require --dev jbboehr/akashi:dev-master
```

## Verify the Command

Composer exposes the package executable at:

```shell
vendor/bin/akashi
```

With no arguments or `--help`, the command prints its supported invocation. `--version` reports the installed Composer
package version.

The source for the following fence places `<!-- akashi-example: hello-world -->` immediately before it:

<!-- akashi-example: hello-world -->

```php
<?php

echo "Hello from Akashi!\n";
```

Write that explicitly marked PHP fence to stdout with:

```shell
vendor/bin/akashi extract \
    --marker-name=akashi-example \
    docs/pages/getting-started.md \
    hello-world
```

The marker name is explicit so the generic command is not tied to Yumemi's comment convention. A successful extraction
writes only PHP source to stdout and preserves the opening tag. Diagnostics use stderr. Exit status `1` means a document
or extraction failure, `2` means invalid command usage, and `70` means an unexpected internal failure.

## Run Examples with PHPUnit

A PHPUnit test can discover a corpus once per data-provider invocation and expose every example as a named data set:

```php
<?php

use jbboehr\Akashi\Example;
use jbboehr\Akashi\Integration\PhpUnit\PhpUnitExampleDataSets;
use jbboehr\Akashi\Integration\PhpUnit\PhpUnitRuntime;
use jbboehr\Akashi\Source\MarkdownSource;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DocumentationExamplesTest extends TestCase
{
    public static function examples(): iterable
    {
        $projectRoot = getcwd();
        if ($projectRoot === false) {
            throw new RuntimeException('Unable to determine the project root.');
        }

        $corpus = MarkdownSource::forProject($projectRoot)
            ->includeFile('README.md')
            ->includeDirectory('docs/pages')
            ->exclude('docs/pages/SUMMARY.md')
            ->load();

        yield from PhpUnitExampleDataSets::fromCorpus($corpus);
    }

    #[DataProvider('examples')]
    public function testDocumentationExample(Example $example): void
    {
        PhpUnitRuntime::assertExample($example);
    }
}
```

Data-set names are the human-readable example labels. Duplicate labels are rejected before the first data set is
yielded, keeping PHPUnit filtering and reports unambiguous. Runtime examples execute in-process by default; Akashi
rejects `<!-- akashi: separate-process -->` until the planned separate-process backend is available. Consequently, a
corpus containing that directive currently produces an error for the corresponding PHPUnit data set. Avoid introducing
the directive into a runtime corpus for now; if an existing project must exclude such examples temporarily, make the
exclusion explicit and track the missing coverage until the backend is available.

## Development

Enter the Nix development shell, install Composer dependencies, and run the checks:

```shell
nix develop
composer install
composer cs
vendor/bin/phpstan analyse
vendor/bin/phpunit
```
