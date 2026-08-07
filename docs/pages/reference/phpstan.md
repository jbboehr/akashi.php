# PHPStan Verification

Akashi can verify a selected documentation-example corpus from a consumer-owned PHPStan `RuleTestCase`. The consumer
continues to provide the rule and any PHPStan extension configuration; Akashi owns example selection, expectation
parsing, temporary files, diagnostic matching, source mapping, and PHPUnit reporting.

PHPStan and PHPUnit are optional consumer dependencies. The current integration targets PHPStan 2.x and PHPUnit 11.5.

## Configure Relevant Examples

Use a predicate for complete control. The following configuration fragments belong inside a consumer test where
`$projectRoot` is already defined; they are not standalone runtime examples.

```php
use jbboehr\Akashi\Example;
use jbboehr\Akashi\Integration\PHPStan\PhpStanExampleConfiguration;

$configuration = PhpStanExampleConfiguration::forProject(
    $projectRoot,
    static fn (Example $example): bool => str_contains($example->code->source, '@phpstan-example'),
);
```

For the common source-token case, use:

```php
use jbboehr\Akashi\Integration\PHPStan\PhpStanExampleConfiguration;

$configuration = PhpStanExampleConfiguration::forTokens(
    $projectRoot,
    '//!',
    '@phpstan-example',
);
```

Token matching is case-sensitive and examines only extracted example code. Blank or duplicate tokens are rejected. The
selected subcorpus retains source order and must contain at least one example.

Add `VerifiesPhpStanExamples` to the consumer's `RuleTestCase`, then call
`$this->assertPhpStanExamples($corpus, $configuration)`. See
[Getting Started](../getting-started.md#verify-examples-with-phpstan) for a complete test class.

## Diagnostic Expectations

The MVP expectation syntax is a standalone line beginning with optional horizontal whitespace and `//!`:

```php
<?php

//! argument has an incompatible unit
operationThatPHPStanShouldReject();
```

The text after the marker must be nonempty. It is matched case-sensitively as a substring of the diagnostic message plus
its optional tip. A trailing marker after PHP code on the same line is not an expectation.

Matching follows strict rules:

- The actual diagnostic count must equal the expectation count.
- A relevant example without expectations must analyze cleanly.
- Every expectation is assigned to a distinct diagnostic.
- The assignment is deterministic and non-greedy, so overlapping broad and narrow substrings can still match correctly.
- Expectations remain in authored order for reporting.

PHPStan identifiers are retained as diagnostic metadata and displayed when available, but `//!` currently matches only
message and tip text. A future identifier-oriented syntax is deferred.

## Analysis Lifecycle

The trait performs one guarded corpus-level lifecycle:

1. Select and parse all relevant examples.
2. Reject unsafe declaration loading before any example is required.
3. Write one private temporary PHP file per example.
4. Change to the configured project root and require every file exactly once.
5. Analyze each file independently through the public `gatherAnalyserErrors()` API.
6. Map analyzer lines back to maintained Markdown lines and match expectations.
7. Restore process state and remove every temporary artifact.

Loading the whole selected corpus first makes declarations visible to reflection when another example is analyzed. The
preflight rejects direct `exit` or `die`, `__halt_compiler()`, built-in `define()`, duplicate class-like, function, or
global-constant declarations, and declarations already present in the hosting process.

Requiring an example executes its top-level PHP. This integration therefore accepts only trusted, runtime-safe
documentation examples. It captures output and restores the working directory, error-reporting level, and output-buffer
stack, but it is not a sandbox. A cleanup failure is reported without discarding the original loading or analyzer
failure.

Temporary paths may remain in low-level diagnostic metadata, but failure reports prefer the maintained Markdown path and
line whenever a source mapping is available.
