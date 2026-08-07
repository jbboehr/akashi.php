# Getting Started

Akashi targets PHP 8.2 and later.

## Installation

Until the first tagged release, install the development branch:

```shell
composer require --dev jbboehr/akashi:dev-master
```

PHPUnit and PHPStan are optional integrations rather than Akashi runtime dependencies. Install the tools used by the
sections you follow if the consuming project does not already provide compatible versions:

```shell
composer require --dev phpunit/phpunit:^11.5
composer require --dev phpstan/phpstan:^2
```

PHPStan example verification also uses PHPUnit's `RuleTestCase` reporting path, so that workflow needs both packages.

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
use jbboehr\Akashi\Execution\RuntimeConfiguration;
use jbboehr\Akashi\Integration\PhpUnit\PhpUnitExampleDataSets;
use jbboehr\Akashi\Integration\PhpUnit\PhpUnitRuntime;
use jbboehr\Akashi\Source\MarkdownSource;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DocumentationExamplesTest extends TestCase
{
    public static function examples(): iterable
    {
        $projectRoot = dirname(__DIR__);

        $corpus = MarkdownSource::forProject($projectRoot)
            ->includeDirectory('docs/examples')
            ->load();

        yield from PhpUnitExampleDataSets::fromCorpus($corpus);
    }

    #[DataProvider('examples')]
    public function testDocumentationExample(Example $example): void
    {
        $runtime = RuntimeConfiguration::forProject(dirname(__DIR__));

        PhpUnitRuntime::assertExample($example, $runtime);
    }
}
```

Data-set names are the human-readable example labels. Duplicate labels are rejected before the first data set is
yielded, keeping PHPUnit filtering and reports unambiguous. Runtime examples execute in-process by default. The runtime
configuration gives both backends an explicit project root. An in-process example can normally use the autoloader that
PHPUnit has already loaded. A child process does not inherit that PHP state, so configure a bootstrap such as
`withBootstrap('vendor/autoload.php')` when separate-process examples use project or dependency symbols.

The illustrative `docs/examples` directory should contain only documents whose PHP fences are executable examples.
Akashi does not yet have a per-block ignore or compile-only directive. Do not include a mixed reference-documentation
tree wholesale when it also contains setup fragments or incomplete PHP excerpts; include executable documents
explicitly, or label non-executable fragments with a language other than `php`.

An immediately associated `<!-- akashi: separate-process -->` directive routes one example to a child PHP process. The
directive overrides an in-process configuration default. To route every unmarked example through the child backend, use
`withDefaultExecutionMode(ExecutionMode::SeparateProcess)` after importing `jbboehr\Akashi\Execution\ExecutionMode`.
Calling `assertExample()` without configuration remains available for ordinary in-process examples, but a
separate-process example is rejected unless the caller supplies the explicit project root needed to launch it safely.

An explicitly configured in-process bootstrap is loaded once per PHPUnit process. Use it for persistent setup such as
autoloaders and declarations; Akashi restores bootstrap changes to the working directory, error-reporting level, and
output-buffer stack after the example.

## Verify Examples with PHPStan

Projects that test a PHPStan rule can reuse the same corpus from a `RuleTestCase`. This example assumes the test file is
directly under `tests/` and selects examples containing either a diagnostic expectation or an explicit project token:

```php
<?php

use jbboehr\Akashi\Example;
use jbboehr\Akashi\Integration\PHPStan\PhpStanExampleConfiguration;
use jbboehr\Akashi\Integration\PHPStan\VerifiesPhpStanExamples;
use jbboehr\Akashi\Source\MarkdownSource;
use PHPStan\Rules\Functions\CallToFunctionParametersRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<CallToFunctionParametersRule> */
final class DocumentationPhpStanExamplesTest extends RuleTestCase
{
    use VerifiesPhpStanExamples;

    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(CallToFunctionParametersRule::class); // @phpstan-ignore phpstanApi.classConstant
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__) . '/extension.neon'];
    }

    public function testDocumentationExamples(): void
    {
        $projectRoot = dirname(__DIR__);
        $corpus = MarkdownSource::forProject($projectRoot)
            ->includeDirectory('docs/examples')
            ->load();
        $configuration = PhpStanExampleConfiguration::forTokens(
            $projectRoot,
            '//!',
            '@phpstan-example',
        );

        $this->assertPhpStanExamples($corpus, $configuration);
    }
}
```

A standalone line beginning with `//!` records a case-sensitive substring expected in one PHPStan diagnostic message or
tip. Diagnostic counts must match exactly, and every expected substring receives a distinct diagnostic. Relevant
examples without markers must analyze cleanly.

Akashi writes each selected example to a private temporary PHP file, loads every selected file once so declarations are
available to reflection, and then analyzes each file independently. Loading executes top-level code, so this integration
is for trusted, runtime-safe project documentation. Akashi rejects direct `exit`, `die`, `__halt_compiler()`, built-in
`define()`, duplicate declarations, and declarations already present in the hosting process before it loads any selected
file. It captures output, restores the working directory and error-reporting level, removes its temporary files, and
maps PHPStan lines back to maintained Markdown lines in failure reports.

## Next Steps

Continue with [Authoring Markdown Examples](authoring-markdown.md) for discovery, fence, marker, and directive rules.
The [Reference](reference/README.md) documents each implemented integration, while
[Compatibility and Limitations](compatibility.md) and the [Roadmap](roadmap.md) identify behavior that is not
implemented yet.

## Development

Enter the Nix development shell, install Composer dependencies, and run the checks:

```shell
nix develop
composer install
composer cs
vendor/bin/phpstan analyse
vendor/bin/phpunit
```
