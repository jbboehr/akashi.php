# PHPStan

A documentation example can be executed at runtime and independently checked as a static-analysis fixture. PHPStan is an
optional, first-class integration: projects that do not need it can use the Markdown and PHPUnit workflow without
installing or configuring PHPStan.

The consumer supplies its PHPStan rule and extension configuration. Akashi supplies corpus selection, expectation
parsing, temporary analysis files, diagnostic matching, source-line mapping, and PHPUnit reporting through PHPStan's
`RuleTestCase`.

## Express an Expected Diagnostic

The current syntax is a standalone `//!` line followed by a case-sensitive diagnostic substring:

```php
//! argument has an incompatible unit
operationThatPHPStanShouldReject();
```

The marker text must be nonempty. A trailing marker on the same line as PHP code is not recognized. Akashi requires the
actual and expected diagnostic counts to match and assigns every expectation to a distinct diagnostic. A selected
example with no expectations must analyze cleanly. Assignment considers the complete expectation/diagnostic set rather
than committing to the first greedy substring match, so overlapping broad and narrow expectations remain deterministic.

This text-oriented syntax is implemented for current consumer compatibility. PHPStan diagnostic identifiers are retained
and shown when available, but identifier-based expectations remain deferred.

## Select Relevant Examples

Select with any project-owned predicate:

```php
<?php

use jbboehr\Akashi\Example;
use jbboehr\Akashi\Integration\PHPStan\PhpStanExampleConfiguration;

$configuration = PhpStanExampleConfiguration::forProject(
    $projectRoot,
    static fn (Example $example): bool => str_contains($example->code->source, '@analyze-example'),
);
```

For a list of case-sensitive source tokens, use the convenience constructor:

```php
<?php

use jbboehr\Akashi\Integration\PHPStan\PhpStanExampleConfiguration;

$configuration = PhpStanExampleConfiguration::forTokens(
    $projectRoot,
    '//!',
    '@analyze-example',
);
```

Token names are project policy, not Akashi directives. Blank or duplicate tokens are rejected, and the selected
subcorpus must not be empty.

## Connect a RuleTestCase

Add `VerifiesPhpStanExamples` to the consumer's `RuleTestCase`, build the same corpus used for runtime tests, and call:

```php
$this->assertPhpStanExamples($corpus, $configuration);
```

The consumer still implements `getRule()` and, when needed, `getAdditionalConfigFiles()` in the normal PHPStan way. See
[Reuse Examples for Runtime and PHPStan](../guides/reuse-runtime-phpstan.md) for a complete combined pattern and a clear
division between Akashi, PHPStan, and project-owned setup.

## Analysis Lifecycle and Trust

Akashi parses all selected examples and validates their declarations before loading any of them. It rejects direct
`exit` or `die`, `__halt_compiler()`, built-in `define()`, duplicate class-like, function, or global-constant
declarations, and declarations already present in the hosting process. It then writes private temporary PHP files,
requires every selected file once so declarations are visible to reflection, and analyzes each file independently via
PHPStan's public `gatherAnalyserErrors()` API.

Requiring the files executes their top-level code. PHPStan verification is therefore for trusted, runtime-safe project
documentation. Akashi captures output, restores the working directory, error-reporting level, and output-buffer stack,
and removes temporary artifacts, but it is not a sandbox.

Analyzer lines are translated back to maintained Markdown lines when the current mapping supports them. Low-level
diagnostic metadata may retain a temporary path, while the user-facing failure report prefers the original document.
