# PHPUnit

<figure class="logion" data-logion="OSD 59:1">
<div class="logion-text">
<blockquote>
<p>The judge received the common stone without asking whether the quarry had named it white; he weighed it once beneath
the lamp, and the court recorded the measure even when no accusation followed.</p>
</blockquote>
<p class="logion-citation">— <cite>Ordinances of the Synthetic Dawn 59:1</cite></p>
</div>
<img src="../images/logia/OSD-59_1.webp" alt="A plain stone weighed on a bronze balance beneath a luminous judicial lamp" width="960" height="540" loading="eager" fetchpriority="high">
</figure>

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

Without an override, the ordinary trait selects the in-process defaults. When examples need an explicit project working
directory, a bootstrap, or child-process execution, `VerifiesPhpUnitExampleSuite` keeps the corpus and runtime
configuration in one immutable definition:

```php
<?php

use jbboehr\Akashi\Execution\RuntimeConfiguration;
use jbboehr\Akashi\Integration\PhpUnit\PhpUnitExampleSuite;
use jbboehr\Akashi\Integration\PhpUnit\VerifiesPhpUnitExampleSuite;
use jbboehr\Akashi\Source\DocumentationSource;
use PHPUnit\Framework\TestCase;

final class ConfiguredDocumentationExamplesTest extends TestCase
{
    use VerifiesPhpUnitExampleSuite;

    protected static function akashiExampleSuite(): PhpUnitExampleSuite
    {
        $projectRoot = dirname(__DIR__);

        return new PhpUnitExampleSuite(
            corpus: DocumentationSource::forProject($projectRoot)
                ->includeFile('README.md')
                ->load(),
            runtimeConfiguration: RuntimeConfiguration::forProject($projectRoot)
                ->withBootstrap('vendor/autoload.php'),
        );
    }
}
```

The suite hook runs once per data-provider invocation while PHPUnit builds the data sets. Each data set carries only its
example and the shared runtime configuration; Akashi keeps no mutable static suite registry. Use either PHPUnit trait in
one test class, not both.

The runtime configuration is immutable. Its project root is canonicalized immediately, and its bootstrap must be a
readable file that resolves inside that root. Existing users of `VerifiesPhpUnitExamples` may instead continue to
override `akashiRuntimeConfiguration()` alongside `akashiExampleCorpus()`.

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

For an example whose intended result is a thrown exception, place `expect-exception` metadata immediately inside its PHP
fence:

<!-- akashi: example=expected-domain-exception -->

```php
// akashi: expect-exception=DomainException
// akashi: expect-exception-message="Invalid documentation input", expect-exception-code=73

throw new DomainException('Invalid documentation input.', 73);
```

The visible comment may appear anywhere and applies to the whole example. Prefer placing it immediately before the
operation expected to throw; Akashi does not attempt to infer control flow or enforce that order. An equivalent
`<!-- akashi: expect-exception=DomainException -->` comment may instead precede the fence when surrounding prose makes
the failure clear or extracted PHP should not contain Akashi metadata. Its optional message constraint uses a second
`<!-- akashi: expect-exception-message="Invalid documentation input" -->` comment, and its optional code constraint uses
`<!-- akashi: expect-exception-code=73 -->`. Adjacent HTML and inline properties are merged, but each property may occur
only once.

The type name is interpreted globally, and a subclass satisfies a parent-class or interface expectation. Akashi checks
the type in the selected runtime, so application exception classes may come from its configured bootstrap or Composer
autoloader; a separate-process type may exist only inside the child. A missing throwable, a different throwable type, or
cleanup failure fails the PHPUnit data set with the maintained documentation location. When present,
`expect-exception-message` requires a nonempty, case-sensitive substring in the actual message, matching PHPUnit's
`expectExceptionMessage()` semantics. `expect-exception-code` accepts a signed base-10 integer in PHP's integer range
and requires exact equality with the actual exception code. A runtime string code, such as a PDO SQLSTATE, remains
available for type and message matching but is reported as a mismatch when an integer code was expected.

The contract is not a general “any failure is success” mode. In particular, a child exit, signal, timeout, startup
failure, or malformed exception report remains a process or infrastructure failure rather than an expected exception.

## Exact Output

Use `expect-output` when stdout itself is part of the example's contract:

<!-- akashi: example=phpunit-exact-output -->

```php
// akashi: expect-output="Hello, Akashi!\n"

echo "Hello, Akashi!\n";
```

The quoted value uses JSON string escaping. Akashi compares the captured stdout bytes exactly: it does not trim output,
normalize line endings, or perform pattern matching. `expect-output=""` explicitly requires no stdout. Expected output
also works with `expect-exception`, covering bytes emitted before the matching throwable. Akashi checks execution and
exception semantics first so an output mismatch does not hide a more fundamental runtime failure. Stderr continues to
appear in failure diagnostics but cannot be asserted in this release.

## Skips and Failures

An authored `<!-- akashi: skip -->` directive remains a named data set, but PHPUnit reports it as skipped before Akashi
configures, transforms, bootstraps, or executes the example. It does not remove the example from PHPStan or extraction.

An authored `compile-only` directive also remains a named data set. Akashi validates its PHP syntax against the running
host version and records one assertion without applying runtime transforms, loading a bootstrap, or executing the code.
This is useful for valid illustrative fragments that should remain available to PHPStan and extraction. It cannot be
combined with `separate-process`, an expected exception, or expected output; `skip` takes precedence when both
dispositions are present. Compile-only governs this PHPUnit path only. PHPStan verification requires selected files and
executes their top-level code, so exclude unsafe compile-only fragments from the PHPStan subcorpus.

Successful examples record one completion assertion even when they contain no native assertion. Failures report the
example ID, label, maintained documentation location when available, failure phase, cause, captured stdout and stderr,
and cleanup problems. The original exception remains in the exception chain.

In-process execution is trusted-code isolation, not a sandbox. Read
[Compatibility and Safety](../reference/compatibility.md) before running generated, third-party, or otherwise untrusted
documentation.
