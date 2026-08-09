# Roadmap

This roadmap records direction, not release-number commitments. The current Markdown workflow is usable without the
features below. Both recorded consumer migrations are complete; the immediate project work is stabilization of the
documented pre-1.0 API.

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

The pre-release API review classified every autoloadable declaration as an entry point, canonical model type,
analyzer-independent PHPStan diagnostic type, public exception, or explicit internal detail. The supported surface may
change between minor releases before 1.0, but architecture tests prevent accidental autoloadability from becoming API.

## Source Discovery Ergonomics

A possible post-stabilization convenience is an immutable bulk file include equivalent to:

```php
/** @param iterable<ProjectPath|string> $paths */
public function includeFiles(iterable $paths): self;
```

It would apply the existing `includeFile()` validation to each project-relative path without coupling Akashi to a file
discovery library. A consumer could feed it relative pathnames from Symfony Finder or another iterator while Akashi's
public signatures remain dependency-neutral. The exact API is deferred until consumer experience demonstrates that
repeated `includeFile()` calls are a material ergonomic problem; Symfony Finder is not a planned Akashi dependency.

## PHPDoc Example Maintainability

Future PHPDoc support should offer three authoring modes:

1. short inline PHPDoc fences for local demonstrations;
2. references to ordinary external PHP files or stable named regions, with the external file as source of truth; and
3. optional synchronized inline copies for renderers that cannot include external content.

Referenced canonical examples are preferred for substantial code because IDEs, formatters, PHPStan, and PHP can operate
on them directly. Named regions are preferred over fragile line-number ranges.

The suggested sequence is:

1. PHPDoc fenced examples.
2. External canonical PHP examples and named regions.
3. Generalized source-location mapping.
4. Check-only synchronization.
5. Check-only formatter integration.
6. Optional write-mode synchronization and formatting.
7. Hidden support-code semantics.
8. Documentation-renderer integrations.

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
results outside PHPUnit, and source maps capable of composing multiple transformations and source origins.

A standalone runner, report formats, and broader plugin seams should follow concrete consumer demand. Akashi will not
add registries or speculative interfaces merely to anticipate them.

## Comparative Review

After the MVP architecture and public API are implemented and recorded, the owner may request a separate,
documentation-only comparison with competing PHP doctest projects. That review is not part of current implementation,
must record every external document consulted, and must not inspect implementation code or silently reshape Akashi's
foundational APIs to match another project.
