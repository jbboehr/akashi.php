# Roadmap

<figure class="logion" data-logion="RAS 33:21">
<div class="logion-text">
<blockquote>
<p>A wheel of violet fire descended behind the cedar ridge, and every abandoned milestone spoke the name of a kingdom
that would not be founded for seven generations.</p>
</blockquote>
<p class="logion-citation">— <cite>Revelation of the Artificial Sun 33:21</cite></p>
</div>
<img src="../images/logia/RAS-33_21.webp" alt="Seven abandoned milestones awakening beneath a violet fire wheel before the silhouette of a future city" width="960" height="540" loading="eager" fetchpriority="high">
</figure>

This roadmap records direction, not release-number commitments. The current Markdown and inline PHPDoc workflows are
usable without the features below. Both recorded consumer migrations are complete; the immediate project work is
stabilization of the documented pre-1.0 API.

## Markdown MVP Acceptance

All recorded MVP acceptance gates are complete:

- Yumemi executes all 43 current PHP fences through Akashi, using child processes for two authored-namespace examples.
- Yumemi verifies all 15 relevant PHPStan examples and eight authored expectations through Akashi.
- Yumemi removed its duplicated documentation-test helpers after its replacement and complete project gates passed.
- Akashi produces byte-identical output for all eight marked Yumemi Apocrypha fixtures in a self-contained compatibility
  gate.
- Yumemi Apocrypha invokes `vendor/bin/akashi` for all eight marked consumer fixtures and removed its duplicated
  extractor after its complete GitHub Actions matrix passed.
- ParaTest compatibility is covered in both TestCase-level and `--functional` test-level scheduling.

The initial API review classified every autoloadable declaration as an entry point, canonical model type,
analyzer-independent PHPStan diagnostic type, public exception, or explicit internal detail. The supported surface may
change between minor releases before 1.0, but architecture tests prevent accidental autoloadability from becoming API.

## Source Discovery Ergonomics

The immutable source manifests now provide a bulk file include equivalent to:

```php
/** @param iterable<ProjectPath|string|\SplFileInfo> $paths */
public function includeFiles(iterable $paths): self;
```

It applies existing file validation to arrays and iterators of project-relative paths. It also accepts `SplFileInfo`, so
Symfony Finder results can be passed directly while Akashi remains dependency-neutral. The built-in recursive directory
selection remains available for projects that do not need an external finder.

## PHPDoc Example Maintainability

PHPDoc support is being delivered through three progressively more maintainable authoring modes:

1. short inline PHPDoc fences for local demonstrations — implemented;
2. references to ordinary external PHP files or stable named regions, with the external file as source of truth —
   implemented; and
3. optional synchronized inline copies for renderers that cannot include external content — parsing, comparison,
   in-memory rewriting, and atomic CLI persistence implemented.

Referenced canonical examples are preferred for substantial code because IDEs, formatters, PHPStan, and PHP can operate
on them directly. Named regions are preferred over fragile line-number ranges.

The synchronization library parses strictly delimited presentations in Markdown and PHPDoc, shares canonical path and
named-region validation with external references, returns typed mismatches, and can render a corrected immutable
document without changing the filesystem. Rewriting preserves surrounding authored bytes and validates the completed
document before returning it. The `sync --check` CLI applies check-only behavior to explicit files with stable
diagnostics, source-labelled unified diffs, and process statuses; `sync --write` validates the full input set and
atomically replaces each changed document with stale-byte protection. Optional PHP-CS-Fixer integration now extracts
inline examples into private valid PHP files, preserves their authored opening tags, ignores file-level formatter
additions outside a protected body boundary, and reports source-labelled diffs in check mode. Checked mismatches can be
applied to their exact inline code spans through a validated in-memory rewrite that preserves surrounding
Markdown/PHPDoc bytes and performs no filesystem access. Write mode validates a second complete formatter pass before
using stale-byte-protected atomic replacement. External canonical PHP remains the normal formatter-friendly mode. The
remaining suggested sequence is:

1. Hidden support-code semantics.
2. Documentation-renderer integrations.

Generated-line mappings now compose across sequential transformations while retaining Markdown, PHPDoc, whole-file, and
named-region origins. Future features that combine several maintained origins will need a richer mapping model, but the
current pipeline no longer requires each transform to reconstruct the original map itself.

No hidden-line syntax is selected. Any future design should remain explicit and compatible with PHP parsers, formatters,
IDEs, renderers, and static analyzers. Akashi integrates with configured formatters rather than becoming a PHP
formatter. The checker and pure rewriter remain independent of the CLI persistence boundary.

## Runtime and Verification

Runtime skip is implemented through PHPUnit's skipped-test reporting. A typed PHPUnit-familiar exception-class
expectation and optional case-sensitive message substring are implemented for in-process examples. Deferred extensions
include code constraints, separate-process exception expectations, broader expected-failure semantics, global ignore and
conditional skip policies with reasons, expected output, compile-only checks, platform conditions, configurable
subprocess timeouts, alternate PHP binaries and INI profiles, and controlled child environments.

PHPStan's identifier-oriented expectation syntax now coexists with legacy `//!` text expectations. Framework-neutral
command verification and external canonical fixture planning are also implemented. A future mapping model may need to
associate one diagnostic with multiple maintained origins.

## External PHPStan Verification

Consumer repositories sometimes construct disposable Composer projects and run PHPStan against installed packages.
Akashi is incrementally replacing their shell-level diagnostic parsing without taking ownership of package installation
or compatibility-matrix orchestration.

The sequence is:

1. **Implemented:** decode PHPStan 1.12 and 2.x JSON into a typed result without discarding top-level analyzer errors,
   file association, or available diagnostic evidence.
2. **Implemented:** compare decoded per-file diagnostics with an explicit expectation map and return typed successful
   matches, complete mismatches, and analyzer-wide errors independently of PHPUnit and `RuleTestCase`.
3. **Implemented:** execute an explicit, boundary-preserving executable and argument vector from an explicit project
   root without constructing a command string, preserving exit status, standard streams, elapsed time, timeout, signal,
   and infrastructure failures as typed evidence.
4. **Implemented:** compose command completion, JSON decoding, and expectation verification while keeping
   non-completion, malformed analyzer output, raw analysis exit status, and completed diagnostic verification distinct.
5. **Implemented:** run the isolated PHPStan 1.12 consumer fixture's real analyzer command through the composed verifier
   and check its structured result at the package boundary.
6. **Implemented:** project selected external canonical PHP examples and named regions into direct analysis paths and
   grouped expectation maps while keeping disposable-project orchestration in the consumer.

The existing `DiagnosticMatcher` and `DiagnosticMatchResult` types remain the lower-level matching contract.
`PhpStanJsonDecoder` and `PhpStanJsonResult` establish the decoder boundary; `PhpStanResultVerifier` and
`PhpStanVerificationResult` establish the framework-neutral verification boundary. `PhpStanCommandVerifier` and its
three result variants establish the composed external-command boundary. `PhpStanExternalFixturePlanner` bridges the
canonical example corpus to that boundary without generating source or invoking PHPStan. Akashi will not construct
temporary Composer projects, add repositories, install dependencies, inspect packages, define another project's
compatibility matrix, or run package-specific runtime assertions. Those responsibilities remain with the consumer
repository.

A standalone Akashi test runner, report formats, and broader plugin seams should follow concrete consumer demand. Akashi
will not add registries or speculative interfaces merely to anticipate them.

## Comparative Review

After the MVP architecture and public API are implemented and recorded, the owner may request a separate,
documentation-only comparison with competing PHP doctest projects. That review is not part of current implementation,
must record every external document consulted, and must not inspect implementation code or silently reshape Akashi's
foundational APIs to match another project.
