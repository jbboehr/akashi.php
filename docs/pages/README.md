{{#title Akashi - Executable documentation testing for PHP}}

![Probatio Verborum Viventium Akashi](images/akashi-banner.png)

# Akashi

**Probatio Verborum Viventium『証』〜ＡＫＡＳＨＩ〜**

Akashi turns PHP examples in Markdown and PHPDoc into tests. A project discovers its examples once, executes them
through PHPUnit, and can reuse a selected part of the same corpus for PHPStan verification or named-example extraction.
In-process execution is the normal runtime path; examples that need process isolation can opt into a child process.

## See It Work

Write ordinary PHP in a Markdown fence:

```php
$result = strtoupper('akashi');

assert($result === 'AKASHI');
```

Connect the containing document through Akashi's PHPUnit trait, then run `vendor/bin/phpunit`. Akashi makes the native
assertion unconditional, isolates the example's variables and declarations, and reports a failure against this
documentation location. This page and the root README are verified through that same path in Akashi's own test suite.

[Follow the Quick Start](quick-start.md) for the complete working test class.

## One Corpus, Several Uses

```text
README.md / docs/ / src PHPDoc
       │
       ▼
  Akashi examples
   ┌──────┼───────────┐
   ▼      ▼           ▼
PHPUnit  PHPStan   extraction
runtime  analysis  / consumers
```

PHPUnit is the usual runtime integration. PHPStan support is optional and project-configured: it lets a rule test
analyze documentation examples independently of executing them. Extraction is a separate CLI workflow for cases where a
stable named example must also become a consumer fixture.

## Choose Your Next Step

- [Quick Start](quick-start.md): install Akashi and run the first documentation test.
- [Authoring Examples](using/authoring.md): choose documents, write fences, and understand labels.
- [PHPUnit](using/phpunit.md): configure runtime execution and PHPUnit reporting.
- [PHPStan](using/phpstan.md): reuse documentation as static-analysis fixtures.
- [Extracting Named Examples](using/extracting.md): emit one stable example for another consumer.
- [Compatibility and Safety](reference/compatibility.md): supported versions, limitations, and trust boundaries.

## Project Status

The Markdown and PHPDoc workflows, in-process and separate-process execution, PHPUnit integration, PHPStan verification,
marked extraction, check/write synchronization, and optional PHP-CS-Fixer checks for inline examples are implemented,
and both recorded consumer migrations are complete. Akashi is pre-1.0, and its categorized public API is usable but may
change between minor releases before 1.0. Deferred work is listed separately in the [Roadmap](project/roadmap.md); it is
not required for the workflow shown above.
