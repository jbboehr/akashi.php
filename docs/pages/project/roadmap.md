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
3. optional synchronized inline copies for renderers that cannot include external content — read-only parsing and
   comparison plus check-only CLI reporting implemented; writes deferred.

Referenced canonical examples are preferred for substantial code because IDEs, formatters, PHPStan, and PHP can operate
on them directly. Named regions are preferred over fragile line-number ranges.

The read-only check-only synchronization path is implemented: the library parses strictly delimited synchronized
presentations in Markdown and PHPDoc, shares canonical path and named-region validation with external references, and
returns typed mismatches without changing files. The `sync --check` CLI applies that behavior to explicit files with
stable diagnostics and process statuses. The remaining suggested sequence is:

1. Check-only formatter integration.
2. Optional write-mode synchronization and formatting.
3. Hidden support-code semantics.
4. Documentation-renderer integrations.

Generated-line mappings now compose across sequential transformations while retaining Markdown, PHPDoc, whole-file, and
named-region origins. Future features that combine several maintained origins will need a richer mapping model, but the
current pipeline no longer requires each transform to reconstruct the original map itself.

No hidden-line syntax is selected. Any future design should remain explicit and compatible with PHP parsers, formatters,
IDEs, renderers, and static analyzers. Akashi should integrate with configured formatters rather than become a PHP
formatter; check-only behavior should precede rewriting docblocks.

## Runtime and Verification

Runtime skip is implemented through PHPUnit's skipped-test reporting. A typed PHPUnit-familiar exception-class
expectation is implemented for in-process examples. Deferred extensions include message and code constraints,
separate-process exception expectations, broader expected-failure semantics, global ignore and conditional skip policies
with reasons, expected output, compile-only checks, platform conditions, configurable subprocess timeouts, alternate PHP
binaries and INI profiles, and controlled child environments.

The PHPStan roadmap includes an identifier-oriented expectation syntax that can coexist with `//!`, richer verifier
results outside PHPUnit, and source mappings that can associate diagnostics with multiple maintained origins.

## External PHPStan Verification

Consumer repositories sometimes construct disposable Composer projects and run PHPStan against installed packages.
Akashi may eventually replace their shell-level diagnostic parsing without taking ownership of package installation or
compatibility-matrix orchestration.

The planned sequence is:

1. Decode PHPStan JSON into a typed result without discarding top-level analyzer errors or file association merely to
   fit the existing `AnalyzerDiagnostic` value.
2. Represent command execution and diagnostic verification independently of PHPUnit and `RuleTestCase`.
3. Add a thin PHPStan command adapter with an explicit project root and a typed argument vector rather than a shell
   command string. Preserve exit status, standard streams, timeouts, signals, launch failures, and malformed analyzer
   output as distinct outcomes.
4. Migrate one consumer fixture and compare the structured result with its existing harness before duplicate parsing is
   removed.
5. Reuse the implemented external canonical PHP examples and named-region authoring for ordinary PHP fixtures while
   keeping disposable-project orchestration in the consumer.

The existing `DiagnosticMatcher` and `DiagnosticMatchResult` types remain the lower-level matching contract. Exact
decoder and adapter names are undecided. Akashi will not construct temporary Composer projects, add repositories,
install dependencies, inspect packages, define another project's compatibility matrix, or run package-specific runtime
assertions. Those responsibilities remain with the consumer repository.

A standalone runner, report formats, and broader plugin seams should follow concrete consumer demand. Akashi will not
add registries or speculative interfaces merely to anticipate them.

## Comparative Review

After the MVP architecture and public API are implemented and recorded, the owner may request a separate,
documentation-only comparison with competing PHP doctest projects. That review is not part of current implementation,
must record every external document consulted, and must not inspect implementation code or silently reshape Akashi's
foundational APIs to match another project.
