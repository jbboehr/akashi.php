# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Discover fenced PHP examples from conventional multiline PHPDoc comments while preserving their original file and line
  locations.
- Build one mixed Markdown/PHPDoc corpus through `DocumentationSource`, including bulk file iterables compatible with
  `SplFileInfo` and Symfony Finder results.
- Extract explicitly marked PHPDoc examples through the existing `vendor/bin/akashi extract` command.
- Reference ordinary canonical PHP files or stable named regions from PHPDoc, deduplicate repeated references, preserve
  both canonical and presentation locations, and reuse the resolved examples in PHPUnit and PHPStan workflows.
- Author `skip`, `separate-process`, `expect-exception`, and optional PHPUnit-compatible `expect-exception-message`
  substring and `expect-exception-code` integer constraints as token-aware PHP line comments in fenced or external
  canonical example code.
- Verify expected exception types, message substrings, and integer codes in both execution backends without treating
  child exits, signals, timeouts, or infrastructure failures as authored exceptions.
- Inspect strictly delimited synchronized Markdown or PHPDoc presentations against canonical whole PHP files or named
  regions, and render corrected documents in memory without changing their surrounding prose or formatting.
- Check synchronized presentations from explicit Markdown or PHP files with `akashi sync --check`, source-labelled
  unified diffs on stderr, stable process statuses, and no writes.
- Update synchronized presentations with `akashi sync --write` after validating the complete input set, using stale-byte
  protection and same-directory atomic replacement for each changed document.
- Check or atomically update inline Markdown and PHPDoc examples with an optional project-installed PHP-CS-Fixer through
  `akashi format --check` or `akashi format --write`, using private temporary inputs, validated rewrites, stale byte
  protection, and source-labelled check-mode diffs.
- Apply checked inline formatting mismatches to exact code spans in a validated immutable document while preserving
  surrounding Markdown or PHPDoc bytes and performing no filesystem writes.
- Decode PHPStan 1.12 and 2.x JSON into typed results that preserve analyzer-wide errors, per-file association, counts,
  available lines, identifiers, tips, and ignorable evidence without loading PHPStan or PHPUnit.
- Verify decoded PHPStan diagnostics against per-file expectation maps without PHPUnit or PHPStan runtime classes,
  preserving successful assignments, complete mismatches, unexpected or missing files, and analyzer-wide errors as typed
  result evidence.
- Execute explicit, boundary-preserving PHPStan command argument vectors without constructing command strings,
  preserving normal exit statuses, standard streams, elapsed time, timeouts, signals, and infrastructure failures as
  typed framework-neutral result evidence.
- Run, decode, and verify external PHPStan commands through typed outcomes that distinguish non-completion, unsupported
  analyzer output, and completed diagnostic verification.
- Project selected external canonical PHP examples and named regions into deterministic PHPStan analysis paths and
  expectation maps without generating temporary source.
- Author PHPStan expectations with exact diagnostic identifiers, optional message-or-tip text, and association to the
  next PHP statement while retaining legacy `//!` substring expectations.

### Changed

- Route the packaged CLI through Symfony Console for generated command help, command listing, Bash/Fish/Zsh completion,
  standard terminal options, and future command growth while preserving exact command names, single-occurrence options,
  required failure diagnostics, and Akashi's stable stream and exit-status contracts.
- `DiagnosticExpectation::$text` is nullable so expectations can constrain text, an identifier, or both; identifier
  expectations may also carry a maintained `sourceLineRange` for their associated statement.
- Make `nix flake check --keep-going -L` the authoritative reproducible repository gate, with separated checks, shared
  immutable Composer closures, supported-PHP and consumer coverage, while retaining conventional PHP baseline CI and
  keeping mutation testing behind an explicit Nix target.
- Compose generated-line mappings across sequential transforms so runtime failures continue to identify maintained
  Markdown, PHPDoc, whole-file, and named-region source lines.
- Support PHPStan 1.12 when consumers explicitly select PHP-Parser 4.19.5, while retaining PHPStan 2.x and PHP-Parser 5
  as the normal development path.
- Support PHP 8.1 through native readonly properties, PHPStan class-level readonly contracts, the Random extension
  polyfill, Symfony Process 6.4, PHPUnit 10.5, and dedicated PHP 8.1 CI and Nix validation.
- `MarkdownSource` now accepts bulk file iterables through `includeFiles()` while remaining the Markdown-only source
  entry point.
- `Example` replaces its former `document`, `location`, and `fence` properties with explicit inline and referenced
  source variants; use `codeOrigin()` for the maintained code location and inspect `source` for presentation details.
- `akashi extract` accepts `--project-root=PATH` when its input document needs project-relative resolution.

## [0.1.0] - 2026-08-10

### Added

- Discover fenced PHP examples from configured Markdown files and directories as a reusable corpus with maintained
  source locations.
- Execute each example as a named PHPUnit test through guarded in-process execution or opt-in child-process isolation.
- Rewrite supported native `assert()` calls into unconditional PHPUnit assertions and report parse, execution, and
  assertion failures against their originating documentation.
- Author runtime skips, separate-process selection, and expected in-process exception types with explicit directives.
- Verify selected examples through PHPStan `RuleTestCase` integration with deterministic matching for authored `//!`
  diagnostics.
- Extract stable, explicitly named examples for consumer fixtures through the configurable `vendor/bin/akashi extract`
  command.
- Configure project roots, bootstrap files, and default execution modes through immutable runtime configuration.
- Support PHP 8.2 and later, PHPUnit 10.5 and 11.5, optional PHPStan 2.x integration, and verified ParaTest scheduling.

[Unreleased]: https://github.com/jbboehr/akashi.php/compare/v0.1.0...master
[0.1.0]: https://github.com/jbboehr/akashi.php/releases/tag/v0.1.0
