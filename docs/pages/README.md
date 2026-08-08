{{#title Akashi - Executable documentation testing for PHP}}

![Probatio Verborum Viventium Akashi](images/akashi-banner.png)

# Akashi

**Probatio Verborum Viventium『証』〜ＡＫＡＳＨＩ〜**

Akashi is a PHP library for discovering, executing, and statically verifying examples embedded in documentation.

## Status

The Markdown MVP is usable today: projects can discover and extract PHP fences, select marked examples, execute them
through PHPUnit in-process or in a child process, and verify a relevant subcorpus with PHPStan. The public API remains
provisional until the Yumemi and Yumemi Apocrypha consumer migrations pass their acceptance gates. See
[Compatibility and Limitations](compatibility.md) for the precise current boundary.

## A Small Example

Documentation can contain ordinary executable PHP:

```php
<?php

$greeting = sprintf('Hello, %s!', 'Akashi');

assert($greeting === 'Hello, Akashi!');
```

Point `MarkdownSource` at the document and pass its corpus through `PhpUnitExampleDataSets` and `PhpUnitRuntime`. Each
fence becomes a named PHPUnit data set, and failures report the maintained Markdown location. The complete test class is
in [Getting Started](getting-started.md#run-examples-with-phpunit).

## Trust and Safety

Akashi executes trusted project documentation; its in-process executor is not a security sandbox. It rejects known
process-terminating and persistent-state constructs and restores the limited process state PHP can reverse reliably, but
it cannot contain resource exhaustion, native-extension crashes, dynamically reached `exit` or `die`, or other fatal
behavior that PHP cannot report as a `Throwable`.

Examples requiring process-level containment belong in the separate-process backend. Its executor protects the hosting
test runner, but it does not restrict the example's operating-system permissions or make untrusted code safe. The
PHPUnit facade honors an example's separate-process directive only when the caller supplies runtime configuration with
an explicit project root; it never weakens the requested isolation to in-process execution.

## Assertion Behavior

Akashi's in-process PHPUnit integration treats native `assert()` calls as documentation-test assertions. They execute
unconditionally and do not depend on `zend.assertions`. Consequently, both the assertion expression and its description
are evaluated even in environments where PHP would otherwise compile native assertions out. Documentation examples must
not rely on assertions being production no-ops.

`PhpUnitResultAsserter` turns a successful execution into one explicit completion assertion, so examples without their
own assertions are not considered risky tests. Failures include the maintained Markdown location when available,
captured stdout and stderr, and cleanup failures; the original execution cause remains available through the exception
chain.

`PhpUnitExampleDataSets` exposes a corpus as independently named PHPUnit data sets, and `PhpUnitRuntime` runs each
example through the transformation and executor selected by its directive and runtime configuration. Both backends use
the same result reporting path.

## Start Here

Continue to [Getting Started](getting-started.md) for installation and integration examples. The remaining chapters
cover [Markdown authoring](authoring-markdown.md), the [implemented reference workflows](reference/README.md), current
[compatibility and limitations](compatibility.md), and the [roadmap](roadmap.md).
