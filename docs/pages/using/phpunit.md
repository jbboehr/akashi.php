# PHPUnit

PHPUnit is Akashi's normal runtime integration. `VerifiesPhpUnitExamples` exposes each documentation example as an
independently named test case, while `PhpUnitRuntime` selects the runtime backend and reports its result. The public
integration supports the PHPUnit 10.5 and 11.5 release lines.

## Connect a Corpus

The [Quick Start](../quick-start.md) contains the smallest complete test class. Use the trait and return the corpus from
one protected hook. This example uses the project-owned `DocumentationCorpus` helper defined in
[Test a README and docs/](../guides/test-documentation.md#define-the-source-set):

```php
use jbboehr\Akashi\ExampleCorpus;
use jbboehr\Akashi\Integration\PhpUnit\VerifiesPhpUnitExamples;
use PHPUnit\Framework\TestCase;

final class DocumentationExamplesTest extends TestCase
{
    use VerifiesPhpUnitExamples;

    protected static function akashiExampleCorpus(): ExampleCorpus
    {
        return DocumentationCorpus::load();
    }
}
```

The trait owns the provider and test method so every example retains its deterministic data-set label. The consuming
project remains responsible for source selection and can share the same `ExampleCorpus` with other integrations.

## Configure Runtime Execution

Without an override, the trait selects the in-process defaults. Override its second hook when examples need an explicit
project working directory, a bootstrap, or child-process execution:

```php
<?php

use jbboehr\Akashi\ExampleCorpus;
use jbboehr\Akashi\Execution\RuntimeConfiguration;
use jbboehr\Akashi\Integration\PhpUnit\VerifiesPhpUnitExamples;
use PHPUnit\Framework\TestCase;

final class ConfiguredDocumentationExamplesTest extends TestCase
{
    use VerifiesPhpUnitExamples;

    protected static function akashiExampleCorpus(): ExampleCorpus
    {
        return DocumentationCorpus::load();
    }

    protected static function akashiRuntimeConfiguration(): RuntimeConfiguration
    {
        return RuntimeConfiguration::forProject(dirname(__DIR__))
            ->withBootstrap('vendor/autoload.php');
    }
}
```

The runtime configuration is immutable. Its project root is canonicalized immediately, and its bootstrap must be a
readable file that resolves inside that root.

## Customize the PHPUnit Test

Projects that need a custom test name, additional data-set arguments, filtering, or per-example setup can use the
lower-level adapter and facade directly:

```php
<?php

use jbboehr\Akashi\Example;
use jbboehr\Akashi\Integration\PhpUnit\PhpUnitExampleDataSets;
use jbboehr\Akashi\Integration\PhpUnit\PhpUnitRuntime;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CustomDocumentationExamplesTest extends TestCase
{
    public static function examples(): iterable
    {
        yield from PhpUnitExampleDataSets::fromCorpus(DocumentationCorpus::load());
    }

    #[DataProvider('examples')]
    public function testExample(Example $example): void
    {
        PhpUnitRuntime::assertExample($example);
    }
}
```

This is the same path used by the trait; it does not change execution semantics.

## What In-Process Execution Does

For the default path, Akashi:

1. parses the example as PHP and retains its documentation line mapping;
2. rejects syntax that cannot be isolated soundly in the hosting process;
3. rewrites supported native assertions;
4. places declarations in a generated namespace;
5. evaluates the code with an empty local variable scope;
6. captures output and restores the working directory, error-reporting level, and output-buffer depth;
7. reports the result through PHPUnit.

The example can normally use the Composer autoloader already loaded by PHPUnit. An explicitly configured in-process
bootstrap is loaded with `require_once`, once per PHPUnit process. Use it for persistent setup such as autoloaders and
declarations. Akashi restores its reversible changes to the working directory, error-reporting level, and output-buffer
stack, so a bootstrap should not rely on those top-level changes remaining in effect for later examples.

## Assertion Behavior

PHP may compile native `assert()` calls out when `zend.assertions=-1`. Documentation tests must not disappear with host
configuration, so Akashi rewrites calls that resolve to PHP's native `assert()` into unconditional PHPUnit assertions.

Supported calls provide one assertion value and at most one string, `Throwable`, or null description. Positional and
valid named arguments are accepted:

```php
assert($value > 0);
assert($value > 0, 'The value must be positive.');
assert(assertion: $value > 0, description: 'The value must be positive.');
```

Unsupported argument names, missing or duplicated assertion values, more than two arguments, argument unpacking, and
first-class-callable syntax are rejected with the documentation location. Non-native functions or methods named `assert`
are left alone.

The rewritten call always evaluates both its assertion and description. That differs from a native assertion compiled
out by PHP, so examples must not rely on either argument being a production no-op. A false assertion with a `Throwable`
description throws that object; a string becomes the PHPUnit failure message; otherwise Akashi reports the original
expression and source line.

Separate-process examples are not rewritten. The child PHP process enables native assertion exceptions explicitly.

## Expected Exceptions

For an in-process example whose intended result is a thrown exception, place an `expect-exception` directive immediately
inside its PHP fence:

<!-- akashi-example: expected-domain-exception -->

```php
// akashi: expect-exception DomainException

throw new DomainException('Invalid documentation input.');
```

The visible comment may appear anywhere and applies to the whole example. Prefer placing it immediately before the
operation expected to throw; Akashi does not attempt to infer control flow or enforce that order. An equivalent
`<!-- akashi: expect-exception DomainException -->` comment may instead precede the fence when surrounding prose makes
the failure clear or extracted PHP should not contain Akashi metadata. Do not combine the forms.

The type name is interpreted globally, and a subclass satisfies a parent-class or interface expectation. Akashi checks
the type after runtime setup and execution, so application exception classes may come from the configured bootstrap or
Composer autoloader. A missing throwable, a different throwable type, or cleanup failure fails the PHPUnit data set with
the maintained Markdown location. The mismatch keeps the actual throwable in its exception chain.

This first contract intentionally matches only the throwable type. It does not match messages or codes, and it is not a
general “any failure is success” mode. Expected exceptions are rejected for separate-process examples until that backend
can return throwable identity without scraping child-process error text.

## Skips and Failures

An authored `<!-- akashi: skip -->` directive remains a named data set, but PHPUnit reports it as skipped before Akashi
configures, transforms, bootstraps, or executes the example. It does not remove the example from PHPStan or extraction.

Successful examples record one completion assertion even when they contain no native assertion. Failures report the
example ID, label, maintained Markdown location when available, failure phase, cause, captured stdout and stderr, and
cleanup problems. The original exception remains in the exception chain.

In-process execution is trusted-code isolation, not a sandbox. Read
[Compatibility and Safety](../reference/compatibility.md) before running generated, third-party, or otherwise untrusted
documentation.
